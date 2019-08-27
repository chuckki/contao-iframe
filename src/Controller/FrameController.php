<?php

namespace Chuckki\HvzIframeBundle\Controller;

use Chuckki\ContaoHvzBundle\HvzModel;
use Chuckki\HvzIframeBundle\Services\Authenticator;
use Chuckki\HvzIframeBundle\Services\Communicator;
use Chuckki\HvzIframeBundle\Services\FormBuilder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Haste\Input\Input;
use Http\Discovery\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrameController extends Controller
{

    protected $rootDir;
    protected $session;
    protected $framework;
    private   $authenticator;
    /**
     * @var Communicator
     */
    private $communicator;

    public function __construct(
        string $rootDir,
        Session $session,
        ContaoFramework $framework,
        Authenticator $authenticator,
        Communicator $communicator
    ) {
        $this->rootDir       = $rootDir;
        $this->session       = $session;
        $this->framework     = $framework;
        $this->authenticator = $authenticator;
        $this->communicator  = $communicator;
    }

    /**
     * @param $customer
     *
     * /extern/{customer}/
     *
     * @return Response
     */
    public function loadFrameAction($customer): Response
    {
        $this->authenticator->isUserAuth($customer);
        return $this->render(
            '@ChuckkiHvzIframe/frame.start.html.twig',
            [
                'customer' => $customer
            ]
        );
    }

    /**
     * @param $customer
     * @param $id
     *
     * /extern/{customer}/hvb/{id}/
     *
     * @return Response
     */
    public function getHvbInfo($customer, $id): Response
    {
        $this->authenticator->isUserAuth($customer);
        $orderObj = HvzModel::findById($id);
        if (!$orderObj) {
            throw new NotFoundHttpException('Unknown City');
        }
        $formObj = new FormBuilder($orderObj, $customer);
        return $this->render(
            '@ChuckkiHvzIframe/frame.details.html.twig',
            [
                'customer'     => $customer,
                'hvz'          => $orderObj,
                'hvzForm'      => $formObj->buildForm(),
                'isSubmited'   => false,
                'requestToken' => \RequestToken::get(),
                'formId'       => 'hvzOrderform'
            ]
        );
    }

    /**
     * @param $customer
     * @param $id
     *
     * /extern/{customer}/getprice/{id}
     *
     * @return Response
     */
    public function getHvbPrice($customer, $id): Response
    {
        $this->authenticator->isUserAuth($customer);
        $hvzObj = HvzModel::findById($id);
        if (!$hvzObj) {
            throw new NotFoundException('Hvz not found');
        }
        $startDateString = Input::post('startDate');
        $extraTag        = (int) Input::post('extraTag') - 1;
        $hvzType         = Input::post('hvzType');
        $startDate       = \DateTime::createFromFormat('d.m.Y', $startDateString);
        $endDate         = \DateTime::createFromFormat('d.m.Y', $startDateString);
        $endDate         = $endDate->modify('+' . $extraTag . ' days');
        setlocale(LC_TIME, 'de_DE.utf8', 'de_DE');
        $startTag = strftime('%A', $startDate->getTimestamp());
        setlocale(LC_TIME, 'de_DE.utf8', 'de_DE');
        $endTag = strftime('%A', $endDate->getTimestamp());
        $price  = ($hvzType === 'beidseitig') ? (int) $hvzObj->hvz_double : (int) $hvzObj->hvz_single;
        $order  = [
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

    /**
     * @param $customer
     * @param $id
     *
     * /extern/{customer}/submit/{id}
     *
     * @return Response
     */
    public function checkFormAction($customer, $id): Response
    {
        $this->authenticator->isUserAuth($customer);
        $objHvz = HvzModel::findById($id);
        if (!$objHvz) {
            throw new NotFoundException('Hvz not found');
        }
        $formBuilder = new FormBuilder($objHvz, $customer);
        $objForm     = $formBuilder->buildForm();
        if ($objForm->validate()) {
            $arrData              = $objForm->fetchAll();
            $arrData['uniqueRef'] = $this->communicator->sendNewOrderToBackend($arrData, $customer, $objHvz);
            $this->communicator->cleanUpAndSaveToDB($arrData, $objHvz);
            $this->communicator->sendConfirmationMail($arrData, $objHvz);
            return $this->render(
                '@ChuckkiHvzIframe/orderConfirm.html.twig',
                [
                    'customermail' => $arrData['billingEmail'],
                    'ordernumber'  => $arrData['uniqueRef']
                ]
            );

        }
        // resend orderForm with errors
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
