<?php

declare(strict_types=1);

namespace Nick\Signature\Api\Providers;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for api signature.',
                    'source' => __DIR__ . '/../../config/app-sign.php',
                    'destination' => BASE_PATH . '/config/autoload/app-sign.php',
                ],
            ],
        ];
    }
}
