<?php
namespace Concrete\Core\Error\Handler;

use Config;
use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;

class JsonErrorHandler extends Handler
{

    public function handle()
    {
        $debug = Config::get('concrete.debug.level', 0);

        if ($debug !== DEBUG_DISPLAY_ERRORS) {
            return Handler::DONE;
        }

        if (!$this->isAjaxRequest()) {
            return Handler::DONE;
        }

        $error = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            true
        );

        $response = array(
            'error'  => $error,
            'errors' => array($error['message'])
        );

        if (\Whoops\Util\Misc::canSendHeaders()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json; charset=' . APP_CHARSET, true);
            } else {
                header('Content-Type: text/plain; charset=' . APP_CHARSET, true);
            }
        }

        echo json_encode($response);
        return Handler::QUIT;
    }

    /**
     * Check, if possible, that this execution was triggered by an AJAX request.
     *
     * @return bool
     */
    private function isAjaxRequest()
    {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

}
