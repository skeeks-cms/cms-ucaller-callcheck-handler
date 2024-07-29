<?php
return [
    'components' => [
        'cms' => [
            'callcheckHandlers'             => [
                'ucaller' => [
                    'class' => \skeeks\cms\callcheck\ucaller\UcallerCallcheckHandler::class
                ]
            ]
        ],
    ],
];