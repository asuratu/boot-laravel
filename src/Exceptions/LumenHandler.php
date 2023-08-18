<?php

namespace ZhuiTech\BootLaravel\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LumenHandler extends ExceptionHandler
{
	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		AuthorizationException::class,
		HttpException::class,
		ModelNotFoundException::class,
		ValidationException::class,
	];

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param Request $request
	 * @param Exception $e
	 * @return Response
	 */
	public function render($request, Exception $e)
	{
		$errors = config('boot-laravel.errors');

		if ($e instanceof ModelNotFoundException) {
			$e = new NotFoundHttpException($e->getMessage(), $e);
		}

		/**
		 * 全局异常处理
		 */
		if ($e instanceof HttpResponseException) {
			return $e->getResponse();
		} elseif ($e instanceof AuthenticationException) {
			return response()->json([
				'status' => false,
				'code' => REST_NOT_LOGIN,
				'message' => $errors[REST_NOT_LOGIN]
			], 401);
		} elseif ($e instanceof AuthorizationException) {
			return response()->json([
				'status' => false,
				'code' => REST_NOT_AUTH,
				'message' => $errors[REST_NOT_AUTH]
			], 403);
		} elseif ($e instanceof NotFoundHttpException) {
			return response()->json([
				'status' => false,
				'code' => REST_NOT_FOUND,
				'message' => $errors[REST_NOT_FOUND]
			], 404);
		}

		/**
		 * 未知错误
		 */
		$response = env('APP_DEBUG', config('app.debug', false)) ? [
			'status' => false,
			'code' => REST_EXCEPTION,
			'message' => $e->getMessage(),
			'exception' => get_class($e),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => collect($e->getTrace())->map(function ($trace) {
				return Arr::except($trace, ['args']);
			})->all(),
		] : [
			// 隐藏异常信息
			'status' => false,
			'code' => REST_EXCEPTION,
			'message' => $errors[REST_EXCEPTION],
		];

		$status = $e instanceof HttpException ? $e->getStatusCode() : 500;

		return response()->json($response, $status);
	}
}
