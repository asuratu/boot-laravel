<?php

namespace ZhuiTech\BootLaravel\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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

    /**
     * Prepare exception for rendering.
     *
     * @param Throwable $e
     * @return NotFoundHttpException|Throwable
     */
    protected function prepareException(Throwable $e): NotFoundHttpException|Throwable
    {
        $e = parent::prepareException($e);

        if ($e instanceof MethodNotAllowedHttpException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        }

        return $e;
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     * @return JsonResponse|RedirectResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json($this->error(REST_NOT_LOGIN), 401)
            : redirect()->guest(route('login'));
    }

    /**
     * 返回错误消息
     * @param int  $code
     * @param null $message
     * @return array
     */
    private function error(int $code = REST_EXCEPTION, $message = null): array
    {
        $errors = config('boot-laravel.errors');

        if ($code === 404) {
            $message = '您访问的内容已不存在';
        }

        return [
            'status' => false,
            'code' => $code,
            'message' => $message ?? $errors[$code],
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

    /**
     * Prepare a response for the given exception.
     *
     * @param Request   $request
     * @param Throwable $e
     * @return Response
     */
    protected function prepareResponse($request, Throwable $e): Response
    {
        // 调试模式
        if (!$this->isHttpException($e) && config('app.debug')) {
            return $this->toIlluminateResponse(
                $this->convertExceptionToResponse($e),
                $e
            );
        }

        // 转化成 500 错误，并显示对应消息
        if ($e instanceof RestCodeException) {
            $e = new HttpException(500, $e->getMessage());
        }

        // 转换成 500 错误，但是隐藏错误信息
        if (!$this->isHttpException($e)) {
            $e = new HttpException(500, get_class($e));
        }

        $array = $this->convertExceptionToArray($e);
        return $this->toIlluminateResponse(
            new JsonResponse($array, $array['code']),
            $e
        );
    }

    /**
     * Convert the given exception to an array.
     *
     * @param Throwable $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        // 全局异常处理
        if ($e instanceof AccessDeniedHttpException) {
            return $this->error(REST_NOT_AUTH);
        } elseif ($e instanceof NotFoundHttpException) {
            return $this->error(REST_NOT_FOUND, $e->getMessage());
        } elseif ($e instanceof RestCodeException) {
            return array_merge($this->error(), [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        }

        // 默认异常处理
        return config('app.debug') ? array_merge($this->error(), [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(function ($trace) {
                return Arr::except($trace, ['args']);
            })->all(),
        ]) : $this->error();
    }
}
