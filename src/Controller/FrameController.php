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

    public function checkFormAction(): Response
    {
        $objForm = $this->buildForm();
        if ($objForm->validate()) {
            $arrData = $objForm->fetchAll();
            $output  = "<pre>";
            $output  .= print_r($arrData, true);
        } else {
            $output = "validation error";
        }
        return new Response($output);
    }

    public function getHvbInfo($id): Response
    {
        $formString = $this->buildForm()->generate();
        $insertTags = new InsertTags();
        $objForm    = $insertTags->replace($formString, false);
        $orderObj = HvzModel::findById($id);
        return $this->render(
            '@ChuckkiHvzIframe/frame.details.html.twig',
            [
                'hvz'     => $orderObj,
                'hvzForm' => $objForm,
                'formId'  => 'hvzOrderform'
            ]
        );
    }

    private function buildForm()
    {
        $objForm = new Form(
            'hvzOrderform', 'POST', function ($objHaste) {
            return Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        }
        );
        $objForm->addFormField(
            'year',
            array(
                'label'     => 'Year',
                'inputType' => 'text',
                'eval'      => array('mandatory' => true, 'rgxp' => 'digit')
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
}
