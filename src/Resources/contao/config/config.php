<?php


array_insert($GLOBALS['BE_MOD'], 0, array
(
    'Hvz' => array(
        'iFrameUser' => [
            'tables' => ['tl_hvz_iframe'],
            'table' => ['TableWizard', 'importTable'],
            'list' => ['ListWizard', 'importList']
        ]
    )
));



$GLOBALS['TL_MODELS']['tl_hvz_iframe'] = \Chuckki\HvzIframeBundle\Model\HvzIframeUserModel::class;
