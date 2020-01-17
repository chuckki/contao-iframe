<?php

namespace Chuckki\HvzIframeBundle\Services;

use Chuckki\ContaoHvzBundle\HvzModel;
use Chuckki\ContaoHvzBundle\PushMeMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class Communicator
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var \Twig_Environment
     */
    private $templating;
    /**
     * @var Authenticator
     */
    private $authenticator;
    /**
     * @var OrderManager
     */
    private $orderManager;

    /**
     * Communicator constructor.
     */
    public function __construct(
        Authenticator $authenticator,
        \Swift_Mailer $mailer,
        \Twig_Environment $templating,
        LoggerInterface $logger,
        OrderManager $orderManager
    ) {
        $this->logger        = $logger;
        $this->mailer        = $mailer;
        $this->templating    = $templating;
        $this->authenticator = $authenticator;
        $this->orderManager  = $orderManager;
    }

    public function sendNewOrderToBackend(&$arrSubmitted, string $customer, HvzModel $hvzModel)
    {
        $zusatzTage                = (int) $arrSubmitted['extraTag'] - 1;
        $preisZusatzTag            = (int) $hvzModel->hvz_extra_tag;
        $arrSubmitted['fullPrice'] = $preisZusatzTag * $zusatzTage + (int) $hvzModel->hvz_single;
        setlocale(LC_ALL, 'de_DE');
        $endDate                      = \DateTime::createFromFormat('d.m.Y', $arrSubmitted['startDateInput']);
        $endDate                      = $endDate->modify('+' . $zusatzTage . ' days');
        $arrSubmitted['endDateInput'] = $endDate->format('d.m.Y');
        $date                         = new \DateTime();
        $arrSubmitted['ts']           = $date->format('Y-m-d H:i:s');
        $api_url                      = $GLOBALS['TL_CONFIG']['hvz_api'];
        $api_auth                     = $this->authenticator->getApiPwForUser($customer);
        $arrSubmitted['city']         = $hvzModel->question;
        $arrSubmitted['apiGender']    = 'female';
        if ('Herr' === $arrSubmitted['gender']) {
            $arrSubmitted['apiGender'] = 'male';
        }
        if (!empty($api_url)) {
            // payload with missing value
            $data   = [
                'uniqueRef'      => dechex(time()),
                'reason'         => $arrSubmitted['hvzReason'],
                'plz'            => (int) $arrSubmitted['hvzPlz'],
                'city'           => $hvzModel->question,
                'price'          => $arrSubmitted['fullPrice'] . '',
                'streetName'     => $arrSubmitted['hvzAdresse'],
                'streetNumber'   => '00',
                'dateFrom'       => $arrSubmitted['startDateInput'],
                'dateTo'         => $arrSubmitted['endDateInput'],
                'timeFrom'       => $arrSubmitted['startTime'] . ':00',
                'timeTo'         => $arrSubmitted['endTime'] . ':00',
                'email'          => $arrSubmitted['billingEmail'],
                'length'         => (int) $arrSubmitted['hvzLength'],
                'isDoubleSided'  => $arrSubmitted['hvzType'] === 'beidseitig',
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
                'paymentStatus'  => 'Rechnung',
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
                    $this->logger->alert('APICall fehlgeschlagen', $data);
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
                PushMeMessage::pushMe($pushMe,'iFrame');
            }
        }
        PushMeMessage::pushMe(
            'IS HvbOnline2Backend -> Keine Auftragsnummer: ' . $arrSubmitted['orderNumber'] . '_0 :: '
            . $arrSubmitted['ts'], 'iFrame'
        );
        return $arrSubmitted['orderNumber'] . '_0';
    }

    public function sendConfirmationMail($arrSubmitted, HvzModel $hvzModel): void
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
        $tagesStunde = (int) date('H');
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
        if (!empty($arrSubmitted['hvzAdditionalInfos'])) {
            $additinalInfoHtml =
                'Ihre angegebenen Zusatzinformationen:<br>' . $arrSubmitted['hvzAdditionalInfos'] . '<br>';
            $additinalInfoTxt  = "Ihre angegebenen Zusatzinformationen:\n" . $arrSubmitted['hvzAdditionalInfos'] . "\n";
        }
        $message = (new \Swift_Message(
            'Bestätigung Ihrer Bestellung ' . $arrSubmitted['uniqueRef']
        ))->setFrom(
            'auftrag@halteverbot-beantragen.de',
            'Halteverbot beantragen'
        )->setTo($mailTo)->setBcc('info@halteverbot-beantragen.de')->setReplyTo(
            'info@halteverbot-beantragen.de',
            'Halteverbot beantragen'
        )->setBody(
            $this->templating->render(
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
            $this->templating->render(
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
        if (0 === $this->mailer->send($message)) {
            PushMeMessage::pushMe('Comfirmation Mail not Send:' . $arrSubmitted['uniqueRef'], 'iFrame');
        }
    }

    public function cleanUpAndSaveToDB($arrSubmitted, HvzModel $hvzModel): void
    {
        if ($arrSubmitted['hvzType'] === 'einseitig') {
            $arrSubmitted['type']           = 1;
            $arrSubmitted['hvz_solo_price'] = $hvzModel->hvz_single;
            $arrSubmitted['Genehmigung']    = 'Einfache HVZ mit Genehmigung';
        } else {
            $arrSubmitted['type']           = 2;
            $arrSubmitted['hvz_solo_price'] = $hvzModel->hvz_double;
            $arrSubmitted['Genehmigung']    = 'Doppelseitige HVZ mit Genehmigung';

        }
        if ($arrSubmitted['hvzCarType'] === 'pkw') {
            $arrSubmitted['Fahrzeug'] = 'Fahrzeug bis 3,5t';
        } else {
            $arrSubmitted['Fahrzeug'] = 'Fahrzeug größer 3,5t (LKW)';
        }
        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arrSubmitted['client_ip'] = $_SERVER['REMOTE_ADDR'];
        } else {
            $arrSubmitted['client_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $arrSubmitted['hvzTagesPreis']        = $hvzModel->hvz_extra_tag;
        $arrSubmitted['Rabatt']               = 0;
        $arrSubmitted['Preis']                = $arrSubmitted['fullPrice'];
        $arrSubmitted['rabattCode']           = '';
        $arrSubmitted['rabattValue']          = 0;
        $arrSubmitted['customerId']           = 1;
        $arrSubmitted['submitType']           = 1;
        $arrSubmitted['genehmigungVorhanden'] = 'n';
        $arrSubmitted['Ort']                  = $hvzModel->question;
        $arrSubmitted['PLZ']                  = $arrSubmitted['hvzPlz'];
        $arrSubmitted['Strasse']              = $arrSubmitted['hvzAdresse'];
        $arrSubmitted['vom']                  = $arrSubmitted['startDateInput'];
        $arrSubmitted['bis']                  = $arrSubmitted['endDateInput'];
        $arrSubmitted['vomUhrzeit']           = $arrSubmitted['startTime'];
        $arrSubmitted['bisUhrzeit']           = $arrSubmitted['endTime'];
        $arrSubmitted['wievieleTage']         = $arrSubmitted['extraTag'];
        $arrSubmitted['Meter']                = $arrSubmitted['hvzLength'];
        $arrSubmitted['Zusatzinformationen']  = $arrSubmitted['hvzAdditionalInfos'];
        $arrSubmitted['Grund']                = $arrSubmitted['hvzReason'];
        $arrSubmitted['Geschlecht']           = $arrSubmitted['gender'];
        $arrSubmitted['umstid']               = '';
        $arrSubmitted['firma']                = $arrSubmitted['organization'];
        $arrSubmitted['Name']                 = $arrSubmitted['familyName'];
        $arrSubmitted['Vorname']              = $arrSubmitted['givenName'];
        $arrSubmitted['strasse_rechnung']     = $arrSubmitted['billingStreet'];
        $arrSubmitted['ort_rechnung']         = $arrSubmitted['billingCity'];
        $arrSubmitted['email']                = $arrSubmitted['billingEmail'];
        $arrSubmitted['Telefon']              = $arrSubmitted['billingTel'];
        $arrSubmitted['agbakzeptiert']        = $arrSubmitted['agbAccept'];
        $arrSubmitted['orderNumber']          = $arrSubmitted['uniqueRef'];
        $arrSubmitted['paypal_id']            = '';
        $arrSubmitted['hvzID']                = $hvzModel->id;
        $this->orderManager->createOrderAndSaveToDatabase($arrSubmitted);
    }
}
