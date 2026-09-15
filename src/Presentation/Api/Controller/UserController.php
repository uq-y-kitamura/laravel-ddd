<?php

declare(strict_types=1);

namespace Presentation\Api\Controller;

use Application\User\UseCase\CreateUser\CreateUserUseCase;
use Application\User\UseCase\DeleteUser\DeleteUserInput;
use Application\User\UseCase\DeleteUser\DeleteUserUseCase;
use Application\User\UseCase\GetUser\GetUserInput;
use Application\User\UseCase\GetUser\GetUserUseCase;
use Application\User\UseCase\ListUsers\ListUsersUseCase;
use Application\User\UseCase\UpdateUser\UpdateUserUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Presentation\Api\Request\User\ListUsersRequest;
use Presentation\Api\Request\User\StoreUserRequest;
use Presentation\Api\Request\User\UpdateUserRequest;
use Presentation\Api\Resource\UserJsonPresenter;

/**
 * ユーザー API のコントローラ。
 * ユースケースの呼び出しとレスポンス変換のみを担う薄い層。
 */
final class UserController
{
    public function __construct(
        private readonly ListUsersUseCase $listUsers,
        private readonly GetUserUseCase $getUser,
        private readonly CreateUserUseCase $createUser,
        private readonly UpdateUserUseCase $updateUser,
        private readonly DeleteUserUseCase $deleteUser,
    ) {}

    public function index(ListUsersRequest $request): JsonResponse
    {
        $output = $this->listUsers->execute($request->toInput());

        return new JsonResponse(UserJsonPresenter::collection($output), Response::HTTP_OK);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $output = $this->createUser->execute($request->toInput());

        return new JsonResponse(UserJsonPresenter::item($output), Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $output = $this->getUser->execute(new GetUserInput($id));

        return new JsonResponse(UserJsonPresenter::item($output), Response::HTTP_OK);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $output = $this->updateUser->execute($request->toInput($id));

        return new JsonResponse(UserJsonPresenter::item($output), Response::HTTP_OK);
    }

    public function destroy(string $id): Response
    {
        $this->deleteUser->execute(new DeleteUserInput($id));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
