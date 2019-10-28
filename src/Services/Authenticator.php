<?php


namespace Chuckki\HvzIframeBundle\Services;


use Chuckki\HvzIframeBundle\Model\HvzIframeUserModel;
use Contao\CoreBundle\Exception\AccessDeniedException;

class Authenticator
{

    private $iframeUserModel;

    /**
     * Authenticator constructor.
     */
    public function __construct(HvzIframeUserModel $hvzIframeUserModel)
    {
        $this->iframeUserModel = $hvzIframeUserModel;
    }

    public function getApiPwForUser($customer): string
    {
        $iframeUser = HvzIframeUserModel::findOneBy('user',$customer);

        if(!$iframeUser){
            throw new AccessDeniedException('Access Denied - User not found');
        }

        return $iframeUser->token;
    }

    public function isUserAuth($customer): void
    {
        $iframeUser = HvzIframeUserModel::findOneBy('user',$customer);

        if(!$iframeUser){
            throw new AccessDeniedException('Access Denied - User not found');
        }
    }
}
