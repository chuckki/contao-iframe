<?php

namespace Chuckki\HvzIframeBundle\Controller;

use Chuckki\ContaoHvzBundle\HvzModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\InsertTags;
use Haste\Form\Form;
use Haste\Input\Input;
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

        return $this->render(
            '@ChuckkiHvzIframe/frame.details.html.twig',
            [
                'hvz'     => $orderObj,
                'hvzForm' => $formString->generate(),
                'requestToken' => \RequestToken::get(),
                'formId'  => 'hvzOrderform'
            ]
        );
    }

    private function buildForm(HvzModel $hvzModel)
    {
        $objForm = new Form(
            'hvzOrderform', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        }
        );

        $objForm->addFormField('hvzId',
            array(
                'default'     => $hvzModel->id,
                'label'       => 'hvzId',
                'inputType'   => 'hidden',
                'eval'        => array('mandatory' => true)
            )
        );

        // Ort
        $objForm->addFormField(
            'ort',
            array(
                'default'     => $hvzModel->question,
                'label'       => 'Ort',
                'inputType'   => 'text',
                'eval'        => array('mandatory' => true, 'readonly' => true)
            )
        );
        // PLZ
        $objForm->addFormField(
            'plz',
            array(
                'label'     => 'PLZ',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true, 'rgxp' => 'digit', 'maxlength' => 5, 'minlength' => 4)
            )
        );
        // Strasse
        $objForm->addFormField(
            'strasse',
            array(
                'label'     => 'Straße und Hausnumer',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true)
            )
        );
        // Need a checkbox?
        $objForm->addFormField(
            'termsOfUse',
            array(
                'label'     => array('', 'Einverstanden mit den AGB'),
                'inputType' => 'checkbox',
                'eval'      => array('mandatory' => true)
            )
        );

        // Let's add  a submit button
        $objForm->addFormField(
            'submit',
            array(
                'label'     => 'Submit form',
                'inputType' => 'submit'
            )
        );
        return $objForm;
    }


    public function checkFormAction(): Response
    {
        $hvzId = \Input::post('hvzId');
        $objHvz = HvzModel::findById($hvzId);
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
