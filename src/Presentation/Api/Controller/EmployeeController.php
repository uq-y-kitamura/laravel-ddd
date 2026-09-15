<?php

declare(strict_types=1);

namespace Presentation\Api\Controller;

use Application\Employee\UseCase\CreateEmployeeUseCase;
use Application\Employee\UseCase\DeleteEmployeeUseCase;
use Application\Employee\UseCase\GetEmployeeUseCase;
use Application\Employee\UseCase\ListEmployeesUseCase;
use Application\Employee\UseCase\UpdateEmployeeUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Presentation\Api\Request\Employee\ListEmployeesRequest;
use Presentation\Api\Request\Employee\StoreEmployeeRequest;
use Presentation\Api\Request\Employee\UpdateEmployeeRequest;
use Presentation\Api\Resource\EmployeeJsonPresenter;

/**
 * 社員 API のコントローラ。
 * ユースケースの呼び出しとレスポンス変換のみを担う薄い層。
 */
final class EmployeeController
{
    public function __construct(
        private readonly ListEmployeesUseCase $listEmployees,
        private readonly GetEmployeeUseCase $getEmployee,
        private readonly CreateEmployeeUseCase $createEmployee,
        private readonly UpdateEmployeeUseCase $updateEmployee,
        private readonly DeleteEmployeeUseCase $deleteEmployee,
    ) {}

    public function index(ListEmployeesRequest $request): JsonResponse
    {
        $output = $this->listEmployees->execute($request->toInput());

        return new JsonResponse(EmployeeJsonPresenter::collection($output), Response::HTTP_OK);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $output = $this->createEmployee->execute($request->toInput());

        return new JsonResponse(EmployeeJsonPresenter::item($output), Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $output = $this->getEmployee->execute($id);

        return new JsonResponse(EmployeeJsonPresenter::item($output), Response::HTTP_OK);
    }

    public function update(UpdateEmployeeRequest $request, string $id): JsonResponse
    {
        $output = $this->updateEmployee->execute($request->toInput($id));

        return new JsonResponse(EmployeeJsonPresenter::item($output), Response::HTTP_OK);
    }

    public function destroy(string $id): Response
    {
        $this->deleteEmployee->execute($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
