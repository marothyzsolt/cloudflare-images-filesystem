<?php

namespace MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseDtoGeneratorInterface;
use MarothyZsolt\CloudflareImagesFileSystem\ResponseHandlers\Contracts\ResponseModelInterface;

class ResponseDtoGenerator implements ResponseDtoGeneratorInterface
{

    public function generate(ResponseModelInterface $responseModel, Response $response): ResponseModelInterface
    {
        $responseModel->response = $response;

        return $this->load($responseModel,  $response->object()?->result ?? (object) []);
    }

    public function load(object $model, object $data): object
    {
        if (method_exists($model, 'prepareInit')) {
            $model->prepareInit($data);
        }

        foreach ($data as $key => $item) {
            try {
                $property = new \ReflectionProperty($model, $key);
                $attributes = $property->getAttributes();

                if (Arr::exists($attributes, 0)) {
                    $instance = $attributes[0]->newInstance();
                    if ($instance->type === 'array') {
                        $this->arrayLoader(app()->make($instance->itemType), $item);
                    }
                }
            } catch (\ReflectionException) {}

            $model->$key = $item;
        }

        if (method_exists($model, 'postInit')) {
            $model->postInit();
        }

        return $model;
    }

    private function arrayLoader(object $subModel, array &$item)
    {
        $itemList = $item;
        $item = [];

        foreach ($itemList as $objectItem) {
            $loadedItem = clone $this->load($subModel, (object) $objectItem);
            $item[] = $loadedItem;
        }
    }
}
