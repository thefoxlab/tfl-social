<?php

namespace App\Controllers;

class Home extends FrontController
{
    private $social;
    
    private ?string $facebookToken = null;
    
    protected string $account = 'thefoxlab';
    
    public function __construct()
    {
        parent::__construct();
        $this->folder = "facebook/";
        $this->social = service('tflSocial')->account($this->account);
    }
    
    private function getFacebookToken(): string
    {
        if ($this->facebookToken !== null) {
            return $this->facebookToken;
        }
        
        $this->facebookToken = $this->session->get('facebook_token');
        
        if (empty($this->facebookToken)) {
            $this->facebookToken = 'YOUR_LONG_LIVED_USER_TOKEN';
            $this->session->set('facebook_token', $this->facebookToken);
        }
        
        return $this->facebookToken;
    }
    
    public function index()
    {
        return redirect()->to(
            $this->social
            ->connect()
            ->provider('facebook')
            ->authorizationUrl()
            );
    }
    
    public function callback()
    {
        $response = $this->social
        ->connect()
        ->provider('facebook')
        ->exchangeAuthorizationCode($this->request->getGet('code'));
        
        $this->session->set('facebook_token', $response->accessToken());
        
        return redirect()->to('home/pages');
    }
    
    public function pages()
    {
        $pages = $this->social
        ->connect()
        ->provider('facebook')
        ->accessToken($this->getFacebookToken())
        ->pages()
        ->toArray();
        //echo "<pre>";print_r($pages);exit;
        $data['results'] = $pages;
        
        
        return $this->load_view('page/index',$data);
    }
    
    
    public function page_manage(string $pageId)
    {
        $this->social
        ->account($this->account)
        ->connect()
        ->provider('facebook')
        ->accessToken($this->getFacebookToken())
        ->connectPage($pageId);
        
        $data = [];
        
        $data['pageId'] = $pageId;
        
        $data['facebook'] = $this->social
        ->facebook()
        ->profile()
        ->toArray();
        
        $data['instagramAccounts'] = $this->social
        ->account($this->account)
        ->connect()
        ->provider('facebook')
        ->instagramBusinesses()
        ->toArray();
        //echo "<pre>";print_r($data);exit;
        return $this->load_view('page/details',$data);

    }
    
    
    public function load_page_data(string $provider = '', string $type = '',string $id = '')
    {
        $pageNo = (int) $this->getPostData('page_no');
        
        if ($pageNo <= 0) {
            $pageNo = 1;
        }
        
        $limit = PER_PAGE;
        
        switch (strtolower($provider)) {
            
            case 'facebook':
                return $this->load_facebook_data($type, $limit);
                
            case 'instagram':
                return $this->load_instagram_data($type, $limit);
        }
        
        return $this->response->setJSON([
            'type' => 'error',
            'msg' => 'Invalid provider.'
        ]);
    }
    
    private function load_facebook_data(string $type, int $limit)
    {
        $allowed = [
            'profile',
            'feed',
            'posts',
            'photos',
            'videos',
            'albums'
        ];
        
        if (! in_array($type, $allowed, true)) {
            return $this->response->setJSON([
                'type' => 'error',
                'msg' => 'Invalid request.'
            ]);
        }
        
        try {
            
            switch ($type) {
                
                case 'profile':
                    $result = $this->social
                    ->facebook()
                    ->profile();
                    break;
                    
                default:
                    $result = $this->social
                    ->facebook()
                    ->{$type}([
                        'limit' => $limit
                    ]);
                    break;
            }
            
            $viewData['results'] = $result->toArray();
            
            return $this->response->setJSON([
                'type' => 'success',
                'html' => view(
                    $this->folder.'page/' . $type,
                    $this->buildViewLib($viewData)
                    ),
                'pagination' => method_exists($result, 'pagination')
                ? $result->pagination()
                : []
            ]);
            
        } catch (\Throwable $e) {
            
            return $this->response->setJSON([
                'type' => 'error',
                'msg' => 'Please connect a Facebook Page first.'
            ]);
        }
    }
    
    private function load_instagram_data(string $type, int $limit)
    {
        $allowed = [
            'profile',
            'media',
            'reels',
            'carousel',
            'stories',
            'ownMediaByHashtag',
            'hashtagSearch',
            'recentHashtagMedia'
        ];
        
        if (! in_array($type, $allowed, true)) {
            return $this->response->setJSON([
                'type' => 'error',
                'msg' => 'Invalid request.'
            ]);
        }
        
        $hashtag = trim((string) $this->getPostData('hashtag'));
        
        if ($hashtag === '') {
            
            $this->general->set_table('social_account');
            
            $hashtag = (string) $this->general->get_value(
                'hashtag',
                [
                    'name' => $this->account
                ]
                );
            
            $hashtags = array_filter(array_map('trim', explode(',', $hashtag)));
            $hashtag = $hashtags[0] ?? '';
        }
        
        try {
            
            switch ($type) {
                
                case 'profile':
                    $result = $this->social
                    ->instagram()
                    ->profile();
                    break;
                    
                case 'hashtagSearch':
                    $result = $this->social
                    ->instagram()
                    ->hashtagSearch($hashtag);
                    break;
                    
                case 'recentHashtagMedia':
                    
                    $tag = $this->social
                    ->instagram()
                    ->hashtagSearch($hashtag)
                    ->toArray();
                    
                    if (empty($tag['data'][0]['id'])) {
                        throw new \RuntimeException('Hashtag not found.');
                    }
                    
                    $result = $this->social
                    ->instagram()
                    ->recentHashtagMedia(
                        $tag['data'][0]['id'],
                        [
                            'limit' => $limit
                        ]
                        );
                    break;
                    
                case 'ownMediaByHashtag':
                    $result = $this->social
                    ->instagram()
                    ->ownMediaByHashtag(
                    $hashtag,
                    [
                    'limit' => $limit
                    ]
                    );
                    break;
                    
                default:
                    $result = $this->social
                    ->instagram()
                    ->{$type}([
                        'limit' => $limit
                    ]);
                    break;
            }
            
            $viewData['results'] = $result->toArray();
            
            return $this->response->setJSON([
                'type' => 'success',
                'data'=>$viewData,
                'html' => view(
                    $this->folder.'instagram/' . $type,
                    $this->buildViewLib($viewData)
                    ),
                'pagination' => method_exists($result, 'pagination')
                ? $result->pagination()
                : []
            ]);
            
        } catch (\Throwable $e) {
            
            return $this->response->setJSON([
                'type' => 'error',
                'msg' => 'Please connect an Instagram Business account first.'
            ]);
        }
    }
    
    
    
    public function instagram(string $accountId, string $pageId)
    {
        $data['connection']   =  $this->social
            ->account($this->account)
            ->connect()
            ->provider('facebook')
            ->connectInstagramBusiness($accountId)->toArray();
        $data['fb_page_id'] = $pageId;
        //echo "<pre>";print_r($data);exit;
        return $this->load_view('instagram/details',$data);
        
    }
    
}