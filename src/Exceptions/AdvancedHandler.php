<?php

namespace ZhuiTech\BootLaravel\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class AdvancedHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        RestCodeException::class
    ];

    public function register(): void
    {
        //异常转成json格式
        $this->reportable(function (Throwable $e) {
            $result['exception'] = [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_values(array_filter(explode("\n", $e->getTraceAsString()), function ($line) {
                    return !str_contains($line, '/vendor/');
                })),
            ];
            Log::info($e->getMessage(), $result);
        });

        $this->renderable(function (Throwable $e, $request) {
            // 其它异常渲染为 JSON
            $result = $this->error();
            if (config('app.debug')) {
                $result['exception'] = [
                    'msg' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => array_values(array_filter(explode("\n", $e->getTraceAsString()), function ($line) {
                        return !str_contains($line, '/vendor/');
                    })),
                ];
            }

            // 可渲染异常
            return match (true) {
                method_exists($e, 'render') => $e->render($request),
                $e instanceof Responsable => $e->toResponse($request),
                $e instanceof HttpResponseException => $e->getResponse(),
                $e instanceof ValidationException => $this->invalidJson($request, $e),
                $e instanceof AuthenticationException => $this->unauthenticated($request, $e),
                $this->isHttpException($e) => response()->json($this->handleHttpException($e), $e->getStatusCode(), ['Access-Control-Allow-Credentials' => 'true']),
                default => response()->json($result, 500, ['Access-Control-Allow-Credentials' => 'true']),
            };
        });
    }

    /**
     * 返回错误消息
     *
     * @param int  $code
     * @param null $message
     * @return array
     */
    private function error(int $code = REST_EXCEPTION, $message = null): array
    {
        $errors = config('boot-laravel.errors');

        return [
            'status' => false,
            'code' => $code,
            'msg' => $message ?? $errors[$code],
            'data' => '',
            'request' => request()->fullUrl()
        ];
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param Request             $request
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        $errors = $exception->errors();

        $message = null;
        if (is_array($errors)) {
            $message = Arr::first($errors);
            if (is_array($message)) {
                $message = Arr::first($message);
            }
        }

        return response()->json(array_merge($this->error(REST_DATA_VALIDATE_FAIL, $message), ['errors' => $errors]));
    }

    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json($this->error(REST_NOT_LOGIN), 401)
            : redirect()->guest(route('login'));
    }

    protected function handleHttpException(Throwable $e): array
    {
        if ($e instanceof AccessDeniedHttpException) {
            return $this->error(REST_NOT_AUTH);
        } elseif ($e instanceof NotFoundHttpException) {
            return $this->error(REST_NOT_FOUND);
        } elseif ($e instanceof RestCodeException) {
            return array_merge($this->error(), [
                'status' => false,
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        } else {
            return $this->error(REST_EXCEPTION, $e->getMessage());
        }
    }

}
