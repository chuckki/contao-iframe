<?php


namespace Chuckki\HvzIframeBundle\Services;


use Chuckki\ContaoHvzBundle\HvzModel;
use Haste\Form\Form;
use Haste\Input\Input;


class FormBuilder
{
    private $hvzModel;
    private $customer;

    /**
     * FormBuilder constructor.
     */
    public function __construct(HvzModel $hvzModel, string $customer)
    {
        $this->hvzModel = $hvzModel;
        $this->customer = $customer;
    }

    public function buildForm() :Form
    {
        $objForm = new Form('hvzOrderform', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });
        // hvzAdresse
        // hvzAdresse
        $objForm->addFormField('hvzAdresse', array(
            'default'   => '',
            'label'     => 'Straße und Hausnummer',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // hvzPlz
        $objForm->addFormField('hvzPlz', array(
            'default'   => '',
            'label'     => 'Postleitzahl',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'rgxp' => 'digit', 'maxlength' => 5, 'minlength' => 4)
        ));
        // hvzDatum
        // startDateInput
        $objForm->addFormField('startDateInput', array(
            'default'   => '',
            'label'     => 'Datum',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // extraTag
        $objForm->addFormField('extraTag', array(
            'description' => 'Anzahl der Tage',
            'default'     => 1,
            'label'       => 'Gültigkeitsdauer',
            'inputType'   => 'select',
            'size'        => 1,
            'options'     => array(
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                '10',
                '11',
                '12',
                '13',
                '14',
            ),
            'eval'        => array('mandatory' => true)
        ));
        // startTime
        $objForm->addFormField('startTime', array(
            'default'   => 7,
            'label'     => 'täglich von',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                '7',
                '8',
                '9',
                '10',
                '11',
                '12',
                '13',
                '14',
                '15',
                '16',
                '17',
                '18',
                '19',
                '20',
            ),
            'eval'      => array('mandatory' => true)
        ));
        // endTime
        $objForm->addFormField('endTime', array(
            'default'   => 19,
            'label'     => 'täglich bis',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                '7',
                '8',
                '9',
                '10',
                '11',
                '12',
                '13',
                '14',
                '15',
                '16',
                '17',
                '18',
                '19',
                '20',
            ),
            'eval'      => array('mandatory' => true)
        ));
        // hvzDetails
        // hvzReason
        $objForm->addFormField('hvzReason', array(
            'default'   => 'umzug',
            'label'     => 'Grund für die Stellung',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                'umzug',
                'containergestellung',
                'anlieferung',
                'baustelle',
                'sonstiges'
            ),
            'eval'      => array('mandatory' => true)
        ));
        // hvzLength
        $objForm->addFormField('hvzLength', array(
            'default'   => '15',
            'label'     => 'Länge',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                '5',
                '10',
                '15',
                '20'
            ),
            'eval'      => array('mandatory' => true)
        ));
        // hvzCarType
        $objForm->addFormField('hvzCarType', array(
            'default'   => 'pkw',
            'label'     => 'Fahrzeugtyp',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                'pkw',
                'lkw'
            ),
            'eval'      => array('mandatory' => true)
        ));
        // hvzType
        $objForm->addFormField('hvzType', array(
            'default'   => 'einseitig',
            'label'     => 'Beschilderung',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                'einseitig',
                'beidseitig'
            ),
            'eval'      => array('mandatory' => true)
        ));
        // hvzAdditionalInfos
        $objForm->addFormField('hvzAdditionalInfos', array(
            'default'   => '',
            'label'     => 'Zusatzinformationen',
            'inputType' => 'textarea'
        ));
        // billingData
        // gender
        $objForm->addFormField('gender', array(
            'default'   => '',
            'label'     => 'Anrede',
            'inputType' => 'select',
            'size'      => 1,
            'options'   => array(
                'Herr',
                'Frau'
            ),
            'eval'      => array('mandatory' => true)
        ));
        // organization
        $objForm->addFormField('organization', array(
            'default'   => '',
            'label'     => 'Firma',
            'inputType' => 'text',
        ));
        // organization
        $objForm->addFormField('familyName', array(
            'default'   => '',
            'label'     => 'Name',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // organization
        $objForm->addFormField('givenName', array(
            'default'   => '',
            'label'     => 'Vorname',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // organization
        $objForm->addFormField('billingStreet', array(
            'default'   => '',
            'label'     => 'Strasse/Hausnummer',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // organization
        $objForm->addFormField('billingCity', array(
            'default'   => '',
            'label'     => 'Ort/Plz',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // organization
        $objForm->addFormField('billingEmail', array(
            'default'   => '',
            'label'     => 'E-Mail-Adresse',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // organization
        $objForm->addFormField('billingTel', array(
            'default'   => '',
            'label'     => 'Telefon',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));

        $objForm->addFormField('dataAccept', array(
            'label' => 'Ich stimme zu, dass meine Daten von <a target="_blank" href="https://www.halteverbot-beantragen.de/impressum.html">Confido</a> erhoben und zur Leistungserbringung verarbeitet werden.
Ich kann diese Einwilligung jederzeit mit Wirkung für die Zukunft widerrufen. Weitere Informationen zum Umgang mit Ihren Daten finden Sie <a target="_blank" href="https://www.halteverbot-beantragen.de/datenschutzerklaerung.html">hier</a>.',
            'inputType' => 'checkbox',
            'eval'      => array('mandatory' => true)
        ));

        $objForm->addFormField('agbAccept', array(
            'label'     => 'Ich erkläre mich mit den <a target="_blank" href="/extern/' . $this->customer . '/page/agb/#top">AGB</a>
                    und den <a target="_blank" href="/extern/' . $this->customer . '/page/datenschutzerklaerung/#top">Datenschutzrichtlinien</a>
                    einverstanden',
            'inputType' => 'checkbox',
            'eval'      => array('mandatory' => true)
        ));
        return $objForm;
    }



}
