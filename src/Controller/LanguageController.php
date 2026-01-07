<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LanguageController extends AbstractController
{
    #[Route('/lang/{_locale}', name: 'app_language_switch', requirements: ['_locale' => 'en|hi'])]
    public function switchLanguage(Request $request, string $_locale): Response
    {
        // Store the locale in session
        $request->getSession()->set('_locale', $_locale);
        
        // Debug: Log what's happening
        error_log('Language switch: _locale=' . $_locale);
        error_log('Language switch: referer=' . $request->headers->get('referer'));
        
        // Get the referer URL or fallback to home page
        $referer = $request->headers->get('referer');
        
        if ($referer && $this->isInternalUrl($referer)) {
            // Check if the referer is register or login page by checking the path
            $parsedReferer = parse_url($referer, PHP_URL_PATH);
            $refererPath = $parsedReferer['path'] ?? '';
            
            error_log('Language switch: referer path=' . $refererPath);
            
            if (strpos($refererPath, '/register') !== false || strpos($refererPath, '/login') !== false) {
                // If on register page, redirect back to register page with new locale
                $registerUrl = $this->generateUrl('app_register', ['_locale' => $_locale]);
                error_log('Language switch: Redirecting to register page: ' . $registerUrl);
                return $this->redirect($registerUrl);
            }
            if (strpos($refererPath, '/login') !== false) {
                // If on login page, redirect back to login page with new locale
                $loginUrl = $this->generateUrl('app_login', ['_locale' => $_locale]);
                error_log('Language switch: Redirecting to login page: ' . $loginUrl);
                return $this->redirect($loginUrl);
            }
            return $this->redirect($referer);
        }
        
        // If no referer, redirect to home page with new locale
        $homeUrl = $this->generateUrl('app_user_index', ['_locale' => $_locale]);
        error_log('Language switch: Redirecting to home page: ' . $homeUrl);
        return $this->redirect($homeUrl);
    }
    
    private function isInternalUrl(string $url): bool
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $host = $request->getHost();
        
        return parse_url($url, PHP_URL_HOST) === $host;
    }
}
