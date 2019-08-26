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
     * Communicator constructor.
     */
    public function __construct(Authenticator $authenticator, \Swift_Mailer $mailer, \Twig_Environment $templating, LoggerInterface $logger)
    {
        $this->logger     = $logger;
        $this->mailer     = $mailer;
        $this->templating = $templating;
        $this->authenticator = $authenticator;
    }

    public function sendNewOrderToBackend(&$arrSubmitted, string $customer, HvzModel $hvzModel)
    {
        $zusatzTage                = (int)$arrSubmitted['extraTag'] - 1;
        $preisZusatzTag            = (int)$hvzModel->hvz_extra_tag;
        $arrSubmitted['fullPrice'] = $preisZusatzTag * $zusatzTage + (int)$hvzModel->hvz_single;
        setlocale(LC_ALL, 'de_DE');
        $endDate                      = \DateTime::createFromFormat('d.m.Y', $arrSubmitted['startDateInput']);
        $endDate                      = $endDate->modify('+' . $arrSubmitted['extraTag'] . ' days');
        $arrSubmitted['endDateInput'] = $endDate->format('d.m.Y');
        $date                         = new \DateTime();
        $arrSubmitted['ts']           = $date->format('Y-m-d H:i:s');
        $api_url                      = $GLOBALS['TL_CONFIG']['hvz_api'];
        $api_auth                     = $this->authenticator->getApiPwForUser($customer);
        $arrSubmitted['apiGender']    = 'female';
        if ('Herr' === $arrSubmitted['gender']) {
            $arrSubmitted['apiGender'] = 'male';
        }
        if (!empty($api_url)) {
            // payload with missing value
            $data   = [
                'uniqueRef'      => dechex(time()),
                'reason'         => $arrSubmitted['hvzReason'],
                'plz'            => (int)$arrSubmitted['hvzPlz'],
                'city'           => $hvzModel->question,
                'price'          => $arrSubmitted['fullPrice'] . '',
                'streetName'     => $arrSubmitted['hvzAdresse'],
                'streetNumber'   => '00',
                'dateFrom'       => $arrSubmitted['startDateInput'],
                'dateTo'         => $arrSubmitted['endDateInput'],
                'timeFrom'       => $arrSubmitted['startTime'] . ':00',
                'timeTo'         => $arrSubmitted['endTime'] . ':00',
                'email'          => $arrSubmitted['billingEmail'],
                'length'         => (int)$arrSubmitted['hvzLength'],
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
                'paymentStatus'  => 'invoice',
            ];
            $pushMe = '';
            try {
                // Send order to API
                $client   = new Client([
                    'base_uri' => $api_url,
                    'headers'  => [
                        'Content-Type'  => 'application/json',
                        'authorization' => 'Basic ' . $api_auth,
                    ],
                ]);
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
                PushMeMessage::pushMe($pushMe);
            }
        }
        PushMeMessage::pushMe('IS HvbOnline2Backend -> Keine Auftragsnummer: ' . $arrSubmitted['orderNumber'] . '_0 :: '
                              . $arrSubmitted['ts']);
        return $arrSubmitted['orderNumber'] . '_0';
    }

    public function sendComfirmationMail($arrSubmitted, HvzModel $hvzModel): void
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
        $tagesStunde = (int)date('H');
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
        $message = (new \Swift_Message('Bestätigung Ihrer Bestellung '
                                       . $arrSubmitted['uniqueRef']))->setFrom('auftrag@halteverbot-beantragen.de',
            'Halteverbot beantragen')
            ->setTo($mailTo)
            ->setBcc('apiMovi@projektorientiert.de')
            ->setReplyTo('info@halteverbot-beantragen.de', 'Halteverbot beantragen')
            ->setBody($this->templating->render('@ChuckkiHvzIframe/mail.confirmation.html.twig', [
                'hvzorder'       => $hvzModel,
                'customer'       => $arrSubmitted,
                'grussFormel'    => $grussFormel,
                'additionalInfo' => $additinalInfoHtml,
            ]), 'text/html')
            ->addPart($this->templating->render('@ChuckkiHvzIframe/mail.confirmation.text.twig', [
                'hvzorder'       => $hvzModel,
                'customer'       => $arrSubmitted,
                'grussFormel'    => $grussFormel,
                'additionalInfo' => $additinalInfoTxt,
            ]), 'text/plain');
        if (0 === $this->mailer->send($message)) {
            PushMeMessage::pushMe('Comfirmation Mail not Send:' . $arrSubmitted['uniqueRef'], 'iframe_IS');
        }
    }
}
