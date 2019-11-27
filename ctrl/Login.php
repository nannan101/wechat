<?php

namespace ctrl;

class Login{
    
    public function login($request)
    {
        $post = isset($request->post) ? $request->post : array();
        
        return 'loginfaild';
    }
    
}
