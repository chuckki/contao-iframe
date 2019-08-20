<?php

namespace Chuckki\HvzIframeBundle\Controller;

use Chuckki\ContaoHvzBundle\HvzModel;
use Chuckki\ContaoHvzBundle\PushMeMessage;
use Contao\CoreBundle\Framework\ContaoFramework;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Haste\Form\Form;
use Haste\Input\Input;
use Http\Discovery\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class FrameController extends Controller
{

    protected $rootDir;
    protected $session;
    protected $framework;

    public function __construct(string $rootDir, Session $session, ContaoFramework $framework)
    {
        $this->rootDir   = $rootDir;
        $this->session   = $session;
        $this->framework = $framework;
    }

    public function loadFrameAction($customer): Response
    {
        return $this->render(
            '@ChuckkiHvzIframe/frame.start.html.twig',
            [
                'customer' => $customer
            ]
        );
    }

    public function getHvbInfo($customer, $id): Response
    {
        $orderObj   = HvzModel::findById($id);
        $formString = $this->buildForm($orderObj, $customer);
        //$formString->getWidget('test')->label
        return $this->render(
            '@ChuckkiHvzIframe/frame.details.html.twig',
            [
                'customer'     => $customer,
                'hvz'          => $orderObj,
                'hvzForm'      => $formString,
                'isSubmited'   => false,
                'requestToken' => \RequestToken::get(),
                'formId'       => 'hvzOrderform'
            ]
        );
    }

    public function getHvbPrice($customer, $id): Response
    {
        $hvzObj = HvzModel::findById($id);
        if (!$hvzObj) {
            throw new NotFoundException('Hvz not found');
        }
        $startDateString = Input::post('startDate');
        $extraTag        = (int) Input::post('extraTag') - 1;
        $hvzType         = Input::post('hvzType');
        $startDate = \DateTime::createFromFormat('d.m.Y', $startDateString);
        $endDate   = \DateTime::createFromFormat('d.m.Y', $startDateString);
        $endDate   = $endDate->modify('+' . $extraTag . ' days');
        setlocale( LC_TIME, 'de_DE');
        $startTag  = strftime('%A', $startDate->getTimestamp());
        setlocale( LC_TIME,'de_DE@euro', 'de_DE', 'de', 'ge');
        $endTag    = strftime('%A', $endDate->getTimestamp());
        $price     = ($hvzType === 'beidseitig') ? $hvzObj->hvz_double : $hvzObj->hvz_single;
        $order     = [
            'ort'               => $hvzObj->question,
            'hvzTitel'          => ucfirst($hvzType),
            'startDateName'     => $startTag,
            'startDateValue'    => $startDate->format('d.m.Y'),
            'endDateName'       => $endTag,
            'endDateValue'      => $endDate->format('d.m.Y'),
            'durationDay'       => $extraTag + 1,
            'priceHvz'          => $price,
            'priceFull'         => $price + ($hvzObj->hvz_extra_tag * $extraTag),
            'hvz'               => $hvzObj,
            'extraTag'          => $extraTag,
            'calcPriceExtraTag' => $hvzObj->hvz_extra_tag * $extraTag,
            'priceExtraTag'     => $hvzObj->hvz_extra_tag,
            'day'               => (($extraTag + 1) === 1) ? 'Tag' : 'Tage'
        ];
        return $this->render(
            '@ChuckkiHvzIframe/overviewOrder.html.twig',
            [
                'order' => $order
            ]
        );

    }

    private function buildForm(HvzModel $hvzModel, $customer)
    {
        $objForm = new Form(
            'hvzOrderform', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        }
        );
        // hvzAdresse
        // hvzAdresse
        $objForm->addFormField(
            'hvzAdresse',
            array(
                'default'   => '',
                'label'     => 'Straße und Hausnummer',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // hvzPlz
        $objForm->addFormField(
            'hvzPlz',
            array(
                'default'   => '',
                'label'     => 'Postleitzahl',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true, 'rgxp' => 'digit', 'maxlength' => 5, 'minlength' => 4)
            )
        );
        // hvzDatum
        // startDateInput
        $objForm->addFormField(
            'startDateInput',
            array(
                'default'   => '',
                'label'     => 'Datum',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // extraTag
        $objForm->addFormField(
            'extraTag',
            array(
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
            )
        );
        // startTime
        $objForm->addFormField(
            'startTime',
            array(
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
            )
        );
        // endTime
        $objForm->addFormField(
            'endTime',
            array(
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
            )
        );
        // hvzDetails
        // hvzReason
        $objForm->addFormField(
            'hvzReason',
            array(
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
            )
        );
        // hvzLength
        $objForm->addFormField(
            'hvzLength',
            array(
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
            )
        );
        // hvzCarType
        $objForm->addFormField(
            'hvzCarType',
            array(
                'default'   => 'pkw',
                'label'     => 'Fahrzeugtyp',
                'inputType' => 'select',
                'size'      => 1,
                'options'   => array(
                    'pkw',
                    'lkw'
                ),
                'eval'      => array('mandatory' => true)
            )
        );
        // hvzType
        $objForm->addFormField(
            'hvzType',
            array(
                'default'   => 'einseitig',
                'label'     => 'Beschilderung',
                'inputType' => 'select',
                'size'      => 1,
                'options'   => array(
                    'einseitig',
                    'beidseitig'
                ),
                'eval'      => array('mandatory' => true)
            )
        );
        // hvzAdditionalInfos
        $objForm->addFormField(
            'hvzAdditionalInfos',
            array(
                'default'   => '',
                'label'     => 'Zusatzinformationen',
                'inputType' => 'textarea'
            )
        );
        // billingData
        // gender
        $objForm->addFormField(
            'gender',
            array(
                'default'   => '',
                'label'     => 'Anrede',
                'inputType' => 'select',
                'size'      => 1,
                'options'   => array(
                    'Herr',
                    'Frau'
                ),
                'eval'      => array('mandatory' => true)
            )
        );
        // organization
        $objForm->addFormField(
            'organization',
            array(
                'default'   => '',
                'label'     => 'Firma',
                'inputType' => 'text',
            )
        );
        // organization
        $objForm->addFormField(
            'familyName',
            array(
                'default'   => '',
                'label'     => 'Name',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // organization
        $objForm->addFormField(
            'givenName',
            array(
                'default'   => '',
                'label'     => 'Vorname',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // organization
        $objForm->addFormField(
            'billingStreet',
            array(
                'default'   => '',
                'label'     => 'Strasse/Hausnummer',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // organization
        $objForm->addFormField(
            'billingCity',
            array(
                'default'   => '',
                'label'     => 'Ort/Plz',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // organization
        $objForm->addFormField(
            'billingEmail',
            array(
                'default'   => '',
                'label'     => 'E-Mail-Adresse',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // organization
        $objForm->addFormField(
            'billingTel',
            array(
                'default'   => '',
                'label'     => 'Telefon',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // Need a checkbox?
        $objForm->addFormField(
            'agbAccept',
            array(
                'label'     => 'Ich erkläre mich mit den <a target="_blank" href="/extern/' . $customer . '/page/agb/#top">AGB</a>
                    und den <a target="_blank" href="/extern/' . $customer . '/page/datenschutzerklaerung/#top">Datenschutzrichtlinien</a>
                    einverstanden',
                'inputType' => 'checkbox',
                'eval'      => array('mandatory' => true)
            )
        );
        return $objForm;
    }


    public function checkFormAction($customer, $id): Response
    {
        $objHvz  = HvzModel::findById($id);
        $objForm = $this->buildForm($objHvz, $customer);
        if ($objForm->validate()) {
            $arrData              = $objForm->fetchAll();
            $orderNumber          = $this->sendNewOrderToBackend($arrData, $customer, $objHvz);
            $arrData['uniqueRef'] = $orderNumber;
            $this->sendComfirmationMail($arrData, $objHvz);
            return $this->render(
                '@ChuckkiHvzIframe/orderConfirm.html.twig',
                [
                    'customermail' => $arrData['billingEmail'],
                    'ordernumber'  => $orderNumber
                ]
            );

        } else {
            return $this->render(
                '@ChuckkiHvzIframe/frame.details.html.twig',
                [
                    'customer'     => $customer,
                    'hvz'          => $objHvz,
                    'isSubmited'   => true,
                    'hvzForm'      => $objForm,
                    'requestToken' => \RequestToken::get(),
                    'formId'       => 'hvzOrderform'
                ]
            );
        }
    }


    private function sendNewOrderToBackend(&$arrSubmitted, string $customer, HvzModel $hvzModel)
    {
        switch ($customer) {
            case 'is':
                break;
            default:
                $customer = 'notSet';
        }
        $zusatzTage                = (int) $arrSubmitted['extraTag'] - 1;
        $preisZusatzTag            = (int) $hvzModel->hvz_extra_tag;
        $arrSubmitted['fullPrice'] = $preisZusatzTag * $zusatzTage + (int) $hvzModel->hvz_single;
        setlocale(LC_ALL, 'de_DE');
        $endDate                      = \DateTime::createFromFormat('d.m.Y', $arrSubmitted['startDateInput']);
        $endDate                      = $endDate->modify('+' . $arrSubmitted['extraTag'] . ' days');
        $arrSubmitted['endDateInput'] = $endDate->format('d.m.Y');
        $date                         = new \DateTime();
        $arrSubmitted['ts']           = $date->format('Y-m-d H:i:s');
        $api_url                      = $GLOBALS['TL_CONFIG']['hvz_api'];
        $api_auth                     = 'aWZyYW1lLUlTOm15cHdmb3JJbW1vU2NvdXQyNE92ZXJJZnJhbWU=';
        $arrSubmitted['apiGender']    = 'female';
        if ('Herr' === $arrSubmitted['gender']) {
            $arrSubmitted['apiGender'] = 'male';
        }
        if (!empty($api_url)) {
            $doubleSide = ($arrSubmitted['hvzType'] === 'beidseitig') ? true : false;
            // payload with missing value
            $data   = [
                'uniqueRef'      => dechex(time()),
                'reason'         => $arrSubmitted['hvzReason'],
                'plz'            => (int) ($arrSubmitted['hvzPlz']),
                'city'           => $hvzModel->question,
                'price'          => $arrSubmitted['fullPrice'] . '',
                'streetName'     => $arrSubmitted['hvzAdresse'],
                'streetNumber'   => '00',
                'dateFrom'       => $arrSubmitted['startDateInput'],
                'dateTo'         => $arrSubmitted['endDateInput'],
                'timeFrom'       => $arrSubmitted['startTime'] . ':00',
                'timeTo'         => $arrSubmitted['endTime'] . ':00',
                'email'          => $arrSubmitted['billingEmail'],
                'length'         => (int) ($arrSubmitted['hvzLength']),
                'isDoubleSided'  => $doubleSide,
                'carrier'        => $arrSubmitted['givenName'] . ' ' . $arrSubmitted['familyName'],
                'additionalInfo' => $arrSubmitted['hvzAdditionalInfos'] . 'Genehmigung vorhanden:' . 'nein',
                'firma'          => $arrSubmitted['organization'],
                'vorname'        => $arrSubmitted['givenName'],
                'name'           => $arrSubmitted['familyName'],
                'strasse'        => $arrSubmitted['billingStreet'],
                'ort'            => $arrSubmitted['billingCity'],
                'telefon'        => $arrSubmitted['billingTel'],
                'needLicence'    => true,
                'gender'         => $arrSubmitted['apiGender'],
                'customerId'     => 'iframe_' . $customer,
                'paymentStatus'  => 'in Progress',
            ];
            $pushMe = '';
            try {
                // Send order to API
                $client   = new Client(
                    [
                        'base_uri' => $api_url,
                        'headers'  => [
                            'Content-Type'  => 'application/json',
                            'authorization' => 'Basic ' . $api_auth,
                        ],
                    ]
                );
                $response = $client->post('/v1/order/new', ['body' => json_encode($data)]);
                if (201 !== $response->getStatusCode()) {

                    $logger = $this->get('monolog.logger');
                    $logger->addAlert('APICall fehlgeschlagen', $data);
                    $pushMe = 'IS Hvb2Api:' . $data['uniqueRef'] . "\n StatusCode:" . $response->getStatusCode()
                              . "\nAPICall not found in ModuleHvz.php";
                } else {
                    $responseArray = json_decode($response->getBody(), true);
                    if (!empty($responseArray['data']['uniqueRef'])) {
                        return $responseArray['data']['uniqueRef'];
                    }
                }
            } catch (RequestException $e) {
                $pushMe = 'IS Hvb2Api:' . $data['uniqueRef'] . "\n APICall Catch:" . $e->getMessage();
            }
            if ('' !== $pushMe) {
                PushMeMessage::pushMe($pushMe);
            }
        }
        PushMeMessage::pushMe(
            'IS HvbOnline2Backend -> Keine Auftragsnummer: ' . $arrSubmitted['orderNumber'] . '_0 :: '
            . $arrSubmitted['ts']
        );
        return $arrSubmitted['orderNumber'] . '_0';
    }


    private function sendComfirmationMail($arrSubmitted, HvzModel $hvzModel)
    {
        if (empty($arrSubmitted['billingEmail'])) {
            $mailTo = 'info@halteverbot-beantragen.de';
        } else {
            // check for valid email
            $original_email = $arrSubmitted['billingEmail'];
            $clean_email    = filter_var($original_email, FILTER_SANITIZE_EMAIL);
            if ($original_email === $clean_email && filter_var($original_email, FILTER_VALIDATE_EMAIL)) {
                $mailTo = $original_email;
            } else {
                $mailTo = 'info@halteverbot-beantragen.de';
            }
        }
        $tagesStunde = (int) (date('H'));
        $grussFormel = 'Sehr geehrte Damen und Herren,';
        if (!empty($arrSubmitted['familyName'])) {
            $grussFormel = 'Guten Tag';
            if ($tagesStunde < 10) {
                $grussFormel = 'Guten Morgen';
            }
            if ($tagesStunde >= 18) {
                $grussFormel = 'Guten Abend';
            }
            $grussFormel .= ' ' . $arrSubmitted['gender'] . ' ' . trim($arrSubmitted['familyName']) . ',';
        }
        $additinalInfoHtml = '';
        $additinalInfoTxt  = '';
        if (!$arrSubmitted['hvzAdditionalInfos']) {
            $additinalInfoHtml =
                'Ihre angegebenen Zusatzinformationen:<br>' . $arrSubmitted['hvzAdditionalInfos'] . '<br>';
            $additinalInfoTxt  = "Ihre angegebenen Zusatzinformationen:\n" . $arrSubmitted['hvzAdditionalInfos'] . "\n";
        }
        $message = (new \Swift_Message(
            'Bestätigung Ihrer Bestellung ' . $arrSubmitted['uniqueRef']
        ))->setFrom(
            'info@halteverbot-beantragen.de',
            'Halteverbot beantragen'
        )->setTo($mailTo)->setBcc('apiMovi@projektorientiert.de')->setReplyTo(
            'info@halteverbot-beantragen.de',
            'Halteverbot beantragen'
        )->setBody(
            $this->renderView(
                '@ChuckkiHvzIframe/mail.confirmation.html.twig',
                [
                    'hvzorder'       => $hvzModel,
                    'customer'       => $arrSubmitted,
                    'grussFormel'    => $grussFormel,
                    'additionalInfo' => $additinalInfoHtml,
                ]
            ),
            'text/html'
        )->addPart(
            $this->renderView(
                '@ChuckkiHvzIframe/mail.confirmation.text.twig',
                [
                    'hvzorder'       => $hvzModel,
                    'customer'       => $arrSubmitted,
                    'grussFormel'    => $grussFormel,
                    'additionalInfo' => $additinalInfoTxt,
                ]
            ),
            'text/plain'
        );
        $mailer  = $this->get('swiftmailer.mailer');
        if (0 === $mailer->send($message)) {
            PushMeMessage::pushMe('Comfirmation Mail not Send:' . $arrSubmitted['uniqueRef'], 'iframe_IS');
        }
    }

}
