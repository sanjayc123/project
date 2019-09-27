<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BookUpdateRequest;
use App\Repositories\Interfaces\BookRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class BooksController.
 *
 * @package namespace App\Http\Controllers;
 */
class BooksController extends ApiController
{
    /**
     * @var BookRepository
     */
    protected $repository;

    /**
     * BooksController constructor.
     *
     * @param BookRepository $repository
     */
    public function __construct(BookRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->repository->pushCriteria(app('Prettus\Repository\Criteria\RequestCriteria'));

        $limit   = $request->get('pageSize') ? $request->get('pageSize') : 10;
        $orderBy = $request->get('orderBy') ? $request->get('orderBy') : 'DESC';
        $search  = $request->get('filter') ? trim($request->get('filter')) : '';
        $sortBy  = $request->get('sortBy') ? $request->get('sortBy') : 'books.updated_at';

        if ($sortBy == "user_name") {
            $sortBy = "users.name";
        }

        $books        = $this->repository->index($search, $sortBy, $orderBy);
        $booksData    = $books->paginate($limit, $columns = ['*']);
        $responseData = [
            'books' => (/*($booksData instanceof App\Entities\Book) && */!empty($booksData)) ? $booksData->toArray() : [],
        ];
        return $this->response($responseData);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store(Request $request)
    {
        try {
            $payload = $request->all();

            $rules = [
                'user_id'     => 'required|exists:users,id',
                'title'       => 'required',
                'description' => 'required',
            ];
            $validator = Validator::make($payload, $rules);
            if ($validator->fails()) {
                return $this->response($validator);
            }
            $book     = $this->repository->create($request->all());
            $responseData = [
                'message' => 'Book created.',
                'data'    => (($book instanceof App\Entities\Book) && !empty($book)) ? $book->toArray() : [],
            ];
            return $this->response($responseData);
        } catch (\Exception $e) {
            $responseData = [
                'error' => [
                    'message'     => 'Something went wrong.',
                    'status_code' => 500,
                ],
            ];
            return $this->response($responseData);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $book = $this->repository->find($id);
        $responseData = [
            'message' => 'View Book.',
            'data'    => (/*($book instanceof App\Entities\Book) && */!empty($book)) ? $book->toArray() : [],
        ];
        return $this->response($responseData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $book = $this->repository->find($id);

        return view('books.edit', compact('book'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  BookUpdateRequest $request
     * @param  string            $id
     *
     * @return Response
     *
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update(BookUpdateRequest $request, $id)
    {
        try {

            $this->validator->with($request->all())->passesOrFail(ValidatorInterface::RULE_UPDATE);

            $book = $this->repository->update($request->all(), $id);

            $response = [
                'message' => 'Book updated.',
                'data'    => $book->toArray(),
            ];

            if ($request->wantsJson()) {

                return response()->json($response);
            }

            return redirect()->back()->with('message', $response['message']);
        } catch (ValidatorException $e) {

            if ($request->wantsJson()) {

                return response()->json([
                    'error'   => true,
                    'message' => $e->getMessageBag(),
                ]);
            }

            return redirect()->back()->withErrors($e->getMessageBag())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleted = $this->repository->delete($id);

        if (request()->wantsJson()) {

            return response()->json([
                'message' => 'Book deleted.',
                'deleted' => $deleted,
            ]);
        }

        return redirect()->back()->with('message', 'Book deleted.');
    }
}
