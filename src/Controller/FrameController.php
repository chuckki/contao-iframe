<?php

namespace Chuckki\HvzIframeBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
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
        $this->rootDir      = $rootDir;
        $this->session      = $session;
        $this->framework    = $framework;
    }

    public function loadFrameAction(): Response
    {
        return new Response('hier');
    }
}
