<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{

    /**
     * default status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * get the status code.
     *
     * @return statuscode
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * set the status code.
     *
     * @param [type] $statusCode [description]
     *
     * @return statuscode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Respond.
     *
     * @param array $data
     * @param array $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond($data, $headers = [])
    {
        return response()->json($data, $this->getStatusCode(), $headers);
    }

    /**
     * This function will check if cuurent login user
     * has rights to perform specific activity
     *
     * @param string $permisssionName
     *
     * @return \Illuminate\Http\JsonResponse | bool
     */
    public function validateAccessRights($permisssionName)
    {
        if (!is_null(\Auth::User())) {
            // check if user has permission
            // if user have permission then return true
            if (\Auth::User()->allow($permisssionName) === true) {
                return true;
            }
        }

        // if user is not logged in then they can't access this page
        // or if user don't have permission
        $data = [
            'error' => [
                'message'     => 'You don\'t have access',
                'status_code' => 422,
            ],
        ];

        return $this->response($data);
    }

    /**
     *
     * Error code is used for the mobile platform
     * Status code is used for the angularjs application.
     *
     * @param $data
     * @param null $message
     * @param Request|null $request
     * @param array $headers
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($data, $message = null, Request $request = null, array $headers = [], $statusCode = 200)
    {
        // Only handle object.
        if (is_object($data)) {
            if ($data instanceof \Illuminate\Validation\Validator) {
                $data = [
                    'error' => [
                        'message'     => $data->getMessageBag()->all(),
                        'status_code' => 422,
                    ],
                ];
            }
        }

        // Default format
        $payLoad = [
            'status'    => 0,
            'errorCode' => rand(1, 100),
            'message'   => 'Please input proper data.',
            'data'      => [],
        ];

        // Only handle array.
        if (!empty($data) && is_array($data) && !isset($data['error'])) {
            if (isset($data['message'])) {
                $message = $data['message'];
                unset($data['message']);
                $payLoad['message'] = $message;
            } else {
                $payLoad['message'] = null;
            }

            $payLoad['status']    = 1;
            $payLoad['errorCode'] = $statusCode;
            $payLoad['data']      = $data;
        } else {
            $statusCode = 404;
            if (isset($data['error']) && !empty($data['error'])) {
                $message   = isset($data['error']['message']) ? $data['error']['message'] : null;
                $errorCode = isset($data['error']['status_code']) ? $data['error']['status_code'] : null;
                if (isset($data['error']['status_code'])) {
                    $statusCode = $data['error']['status_code'];
                } else {
                    $statusCode = $data['error']['status_code'] = 404;
                }
            } else {
                $message           = null;
                $errorCode         = $statusCode;
                $payLoad['status'] = 1;
            }

            unset($data['message']);
            $payLoad['message']   = $message;
            $payLoad['errorCode'] = $errorCode;
            $payLoad['data']      = isset($data['data']) ? $data['data'] : null;
        }

        if (!is_array($payLoad['message'])) {
            if (is_null($payLoad['message']) || empty($payLoad['message']) || null == $payLoad['message']) {
                $payLoad['message'] = [];
            } else {
                $payLoad['message'] = (array) $payLoad['message'];
            }
        }        
        return response()->json($payLoad, $statusCode, $headers);
    }
}
