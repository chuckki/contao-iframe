<?php

namespace Chuckki\HvzIframeBundle\Controller;

use Chuckki\ContaoHvzBundle\HvzModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\InsertTags;
use Haste\DateTime\DateTime;
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

    public function loadFrameAction(): Response
    {
        return $this->render('@ChuckkiHvzIframe/frame.start.html.twig');
    }

    public function getHvbInfo($id): Response
    {
        $orderObj   = HvzModel::findById($id);
        $formString = $this->buildForm($orderObj);
        return $this->render('@ChuckkiHvzIframe/frame.details.html.twig', [
            'hvz'          => $orderObj,
            'hvzForm'      => $formString,
            'requestToken' => \RequestToken::get(),
            'formId'       => 'hvzOrderform'
        ]);
    }

    public function getHvbPrice($id): Response
    {
        $hvzObj = HvzModel::findById($id);

        if (!$hvzObj) {
            throw new NotFoundException('Hvz not found');
        }

        $startDateString = Input::post('startDate');
        $extraTag        = (int)Input::post('extraTag') - 1;
        $hvzType         = Input::post('hvzType');

        setlocale(LC_ALL, 'de_DE');
        $startDate = \DateTime::createFromFormat('d.m.Y', $startDateString);
        $endDate   = \DateTime::createFromFormat('d.m.Y', $startDateString);
        $endDate   = $endDate->modify('+' . $extraTag . ' days');
        $startTag  = strftime('%A', $startDate->getTimestamp());
        $endTag    = strftime('%A', $endDate->getTimestamp());
        $price     = ($hvzType === 'beidseitig') ? $hvzObj->hvz_double : $hvzObj->hvz_single;

        $order = [
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

        return $this->render('@ChuckkiHvzIframe/overviewOrder.html.twig', [
            'order' => $order
        ]);

    }


    private function buildForm(HvzModel $hvzModel)
    {
        $objForm = new Form('hvzOrderform', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $objForm->addFormField('hvzId', array(
            'default'   => $hvzModel->id,
            'label'     => 'hvzId',
            'inputType' => 'hidden',
            'eval'      => array('mandatory' => true)
        ));

        // Ort
        $objForm->addFormField('ort', array(
            'default'   => $hvzModel->question,
            'label'     => 'Ort',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'readonly' => true)
        ));
        // PLZ
        $objForm->addFormField('plz', array(
            'label'     => 'PLZ',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'rgxp' => 'digit', 'maxlength' => 5, 'minlength' => 4)
        ));
        // Strasse
        $objForm->addFormField('strasse', array(
            'label'     => 'Straße und Hausnumer',
            'inputType' => 'text',
            'eval'      => array('mandatory' => true)
        ));
        // Need a checkbox?
        $objForm->addFormField('termsOfUse', array(
            'label'     => array('', 'Einverstanden mit den AGB'),
            'inputType' => 'checkbox',
            'eval'      => array('mandatory' => true)
        ));

        // Let's add  a submit button
        $objForm->addFormField('submit', array(
            'label'     => 'Submit form',
            'inputType' => 'submit'
        ));
        return $objForm;
    }


    public function checkFormAction(): Response
    {
        return $this->render('@ChuckkiHvzIframe/orderConfirm.html.twig', [
            'customermail' => 'supermann@nix.de',
            'ordernumber' => '76acf67s'
        ]);

        dump($_POST);
        die;
        $hvzId   = \Input::post('hvzId');
        $objHvz  = HvzModel::findById($hvzId);
        $objForm = $this->buildForm($objHvz);
        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();
            $output  .= "<pre>";
            $output  .= print_r($arrData, true);
        } else {
            $output = "validation error";
        }
        return new Response($output);
    }

}
