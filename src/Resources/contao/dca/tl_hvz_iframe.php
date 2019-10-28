<?php

$GLOBALS['TL_DCA']['tl_hvz_iframe'] = [
    'config' => [
        'dataContainer' => 'Table',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
            )
        )
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['user'],
            'headerFields' => ['user'],
            'flag' => 1,
            'panelLayout' => 'debug;filter;sort,search,limit',
        ],
        'label' => [
            'fields'            => ['user','start','stop'],
            'format'            => 'hier: %s %s %s',
            'showColumns'       => true,
            'label_callback'    => array('tl_thtp_iframe', 'listDates'),
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_hvz_rabatt']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_hvz_rabatt']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_hvz_rabatt']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpleguestbook']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => '
			user,
			token,
			fromDate,
			toDate,
			comments,
			start,
			stop'
    ],
    'subpalettes' => [
        '' => ''
    ],
    'fields' => [
        'id' => [
            'sql' => "int(11) unsigned NOT NULL auto_increment"
        ],
        'user' => [
            'label' => array('User','User wird als Identifikation via "extern/[user]/ benutzt'),
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'sql'                     => "varchar(64) NOT NULL default ''",
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
        ],
        'token' => [
            'label' => array('Token ','zur Identifizierung via Basic Auth'),
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'sql'                     => "varchar(64) NOT NULL default ''",
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
        ],
        'start' => array
        (
            'exclude'                 => true,
            'label'                   => array('Startdatum','Falls gesetzt, wird dieser Code erst dann gültig'),
            'inputType'               => 'text',
            'sql'                     => "varchar(10) NOT NULL default ''",
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
        ),
        'stop' => array
        (
            'exclude'                 => true,
            'label'                   => array('Stopdatum','Stopdatum für die Gültigkeit des Codes.'),
            'inputType'               => 'text',
            'sql'                     => "varchar(10) NOT NULL default ''",
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
        ),
        'comments' => [
            'label' => array('Kommentar','optional'),
            'exclude' => true,
            'search' => true,
            'sql'                     => "text NULL",
            'inputType'               => 'textarea',
        ],
        'tstamp' => [
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ],
    ]
];

class tl_thtp_iframe extends Backend
{

    /**
     * List a particular record
     *
     * @param array
     *
     * @return array
     */
    public function listDates($arrRow)
    {
        $start = ($arrRow['start']) ? date('d.m.Y', (int)$arrRow['start']) :'';
        $stop = ($arrRow['stop']) ? date('d.m.Y', (int)$arrRow['stop']) :'';

        return array(
            $arrRow['user'],
            $start,
            $stop);
    }
}
