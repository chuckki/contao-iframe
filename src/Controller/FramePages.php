<?php


namespace Chuckki\HvzIframeBundle\Controller;


use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

class FramePages extends Controller
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

    private function buildHeadLine(string $headlineArray)
    {
            $arrHeadline = StringUtil::deserialize($headlineArray);
            $headlineString = \is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
            $headlineTag = \is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
            $string = '<h3>'.$headlineString.'</h3>';
            return $string;
    }

    public function showPageInFrameAction($alias)
    {
        switch ($alias){
            case 'impressum':
            case 'widerruf':
            case 'datenschutzerklaerung':
            case 'agb':
                break;
            default:
                throw new PageNotFoundException( 'Page not found: ' . \Environment::get('uri') );
        }

        $pageObj = PageModel::findByAlias($alias);
        $articlesObj = ArticleModel::findByPid($pageObj->id);
        $txt = '';
        foreach ($articlesObj as $articleModel) {
            $objElement = ContentModel::findByPid($articleModel->id);
            /** @var ContentModel $objElement */
            $txt .= $this->buildHeadLine($objElement->headline). $objElement->text;
        }

        $insertTags = new InsertTags();
        $txt    = $insertTags->replace($txt, false);
        return $this->render('@ChuckkiHvzIframe/frame.page.html.twig', [
            'content'          => $txt
        ]);

    }
}
