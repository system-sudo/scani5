<?php
 
namespace App;
 
trait ResponseApi
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
 
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data' => $result,
            'message' => $message
        ];
 
        return response()->json($response, 200);
    }
 
    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
 
    public function sendError($error, $errorMessage = [], $code = 422)
    {
 
        $response = [
            'success' => false,
            'message' => $error
        ];
 
        if (!empty($errorMessage)) {
            $response['error'] = $errorMessage;
        }
 
        return response()->json($response, $code);
    }
    public function sendErrorLocked($error, $code = 403)
    {
 
        $response = [
            'success' => false,
            'message' => $error
        ];
 
        return response()->json($response, $code);
    }


}