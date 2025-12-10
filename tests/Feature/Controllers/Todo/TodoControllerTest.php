<?php

namespace Tests\Feature\Controllers\Todo;

use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Http\Controllers\App\Todo\TodoController;
use App\Models\TodoModel;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TodoControllerTest extends TestCase
{
    // Attribut untuk menyimpan mock objek
    protected $todoModelMock;

    protected $userApiMock;

    // Setup sebelum setiap test => dipanggil sebelum test dijalankan
    protected function setUp(): void
    {
        parent::setUp();

        // Clear mock sebelumnya
        Mockery::close();

        // Mock Inertia::always untuk mengembalikan nilai yang diinginkan
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });

        // Buat mock dengan alias
        $this->todoModelMock = Mockery::mock('alias:'.TodoModel::class);
        $this->userApiMock = Mockery::mock('alias:'.UserApi::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==============================================
    // Test untuk method index(Request $request)
    // ==============================================
    #[Test]
    public function index_berhasil_render_dengan_tidak_ada_akses_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['akses' => [''], 'roles' => ['User']];
        $request = Request::create('/todo', 'GET');
        $request->attributes->set('auth', $auth);

        // Mock Inertia::render untuk mengembalikan response mock
        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->once()
            ->with('app/todo/todo-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function index_berhasil_render_dengan_akses_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['akses' => ['Todo'], 'roles' => ['User']];
        $request = Request::create('/todo', 'GET');
        $request->attributes->set('auth', $auth);

        // Mock Inertia::render untuk mengembalikan response mock
        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->once()
            ->with('app/todo/todo-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function index_berhasil_render_dengan_query_perpage_0()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['akses' => ['Todo'], 'roles' => ['User']];
        $request = Request::create('/todo', 'GET');
        $request->attributes->set('auth', $auth);
        $request->query->set('perPage', 0);

        // Mock Inertia::render untuk mengembalikan response mock
        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->once()
            ->with('app/todo/todo-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function index_berhasil_render_dengan_query_search()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Membuat object user yang terautentikasi dengan akses Todo dan role User
        $auth = (object) [
            'id' => ToolsHelper::generateId(),
            'akses' => ['Todo'],
            'roles' => ['User'],
        ];

        // Membuat request GET dengan parameter search dan perPage
        $request = Request::create('/todo', 'GET', [
            'search' => 'test',  // Kata kunci pencarian
            'perPage' => 5,  // Jumlah item per halaman
        ]);
        $request->attributes->set('auth', $auth);  // Menyimpan data auth ke request

        // Mock behavior untuk query builder TodoModel

        // Mock method query() yang return object model sendiri (fluent interface)
        $this
            ->todoModelMock
            ->shouldReceive('query')
            ->andReturnSelf();

        // Mock where clause untuk filter berdasarkan user_id
        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with('user_id', $auth->id)
            ->andReturnSelf();

        // Mock conditional search dengan method when()
        // Method when hanya dijalankan jika search term tidak kosong
        $this
            ->todoModelMock
            ->shouldReceive('when')
            ->with('test', Mockery::type('callable'))
            ->andReturnUsing(function ($search, $callback) {
                $callback($this->todoModelMock);  // Eksekusi callback search

                return $this->todoModelMock;
            });

        // Mock where clause dengan closure untuk logika pencarian yang kompleks
        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with(Mockery::any(Closure::class))
            ->andReturnSelf();

        // Mock raw SQL queries untuk pencarian yang lebih fleksibel
        $this
            ->todoModelMock
            ->shouldReceive('whereRaw')
            ->andReturnSelf();
        $this
            ->todoModelMock
            ->shouldReceive('orWhereRaw')
            ->andReturnSelf();

        // Mock sorting berdasarkan created_at descending
        $this
            ->todoModelMock
            ->shouldReceive('orderByDesc')
            ->with('created_at')
            ->andReturnSelf();

        // Mock pagination dengan 5 item per halaman
        $this
            ->todoModelMock
            ->shouldReceive('paginate')
            ->with(5)
            ->andReturn('paginatedResult');  // Return value mock

        // Capture props yang dikirim ke view Inertia
        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        // Mock Inertia render dan capture props yang dikirim
        Inertia::shouldReceive('render')
            ->with('app/todo/todo-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        // Pastikan response yang dikembalikan sama dengan mock response
        $this->assertSame($mockResponse, $response);

        // Verifikasi bahwa prop 'todoList' ada dalam props yang dikirim ke view
        $this->assertArrayHasKey('todoList', $capturedProps);

        // Eksekusi lazy closure untuk mendapatkan data todoList yang sebenarnya
        $lazyResult = $capturedProps['todoList']();

        // Pastikan hasil lazy prop sama dengan mock paginated result
        $this->assertEquals('paginatedResult', $lazyResult);
    }

    // ==============================================
    // Test untuk method postChange(Request $request)
    // ==============================================

    #[Test]
    public function post_change_gagal_karena_tidak_memiliki_akses_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];  // Bukan Admin
        $request = Request::create('/hak-akses/delete-selected', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postChange($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk mengolah todo.', $response->getSession()->get('error'));
    }

    #[Test]
    public function post_change_berhasil_menambahkan_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['id' => ToolsHelper::generateId(), 'akses' => ['Todo'], 'roles' => ['User']];
        $request = Request::create(route('todo.change-post'), 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge([
            'title' => 'Test Todo',
            'description' => 'This is a test todo item.',
            'isDone' => false,
        ]);

        // Mock todo model
        $this
            ->todoModelMock
            ->shouldReceive('create')
            ->once()
            ->andReturnTrue();

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postChange($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Todo berhasil ditambahkan.', $response->getSession()->get('success'));
    }

    #[Test]
    public function post_change_gagal_mengubah_todo_dengan_data_tidak_ditemukan()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['id' => ToolsHelper::generateId(), 'akses' => ['Todo'], 'roles' => ['User']];
        $request = Request::create(route('todo.change-post'), 'POST');
        $request->attributes->set('auth', $auth);

        $todoId = ToolsHelper::generateId();
        $request->merge([
            'todoId' => $todoId,
            'title' => 'Test Todo',
            'description' => 'This is a test todo item.',
            'isDone' => false,
        ]);

        // Mock todo model
        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with('id', $todoId)
            ->andReturnSelf();
        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with('user_id', $auth->id)
            ->andReturnSelf();
        $this
            ->todoModelMock
            ->shouldReceive('first')
            ->andReturnNull();

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postChange($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Todo tidak ditemukan.', $response->getSession()->get('error'));
    }

    #[Test]
    public function post_change_berhasil_mengubah_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['id' => ToolsHelper::generateId(), 'akses' => ['Todo'], 'roles' => ['User']];
        $request = Request::create(route('todo.change-post'), 'POST');
        $request->attributes->set('auth', $auth);

        $todoId = ToolsHelper::generateId();
        $request->merge([
            'todoId' => $todoId,
            'title' => 'Test Todo',
            'description' => 'This is a test todo item.',
            'isDone' => false,
        ]);

        // Mock todo model
        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with('id', $todoId)
            ->andReturnSelf();
        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with('user_id', $auth->id)
            ->andReturnSelf();
        $this
            ->todoModelMock
            ->shouldReceive('first')
            ->andReturn($this->todoModelMock);
        $this
            ->todoModelMock
            ->shouldReceive('save')
            ->andReturnTrue();

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postChange($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Todo berhasil diperbarui.', $response->getSession()->get('success'));
    }

    // ==============================================
    // Test untuk method postDelete(Request $request)
    // ==============================================
    #[Test]
    public function post_delete_gagal_karena_tidak_memiliki_akses_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];  // Bukan Admin
        $request = Request::create('/hak-akses/delete-selected', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDelete($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk mengolah todo.', $response->getSession()->get('error'));
    }

    #[Test]
    public function post_delete_berhasil_menghapus_todo()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        // Ubah auth di request
        $auth = (object) ['id' => ToolsHelper::generateId(), 'akses' => ['Todo'], 'roles' => ['User']];
        $request = Request::create(route('todo.delete-post'), 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge([
            'todoIds' => [ToolsHelper::generateId(), ToolsHelper::generateId()],
        ]);

        // Mock todo model
        $this
            ->todoModelMock
            ->shouldReceive('whereIn')
            ->with('id', $request->todoIds)
            ->andReturnSelf();

        $this
            ->todoModelMock
            ->shouldReceive('where')
            ->with('user_id', $auth->id)
            ->andReturnSelf();

        $this
            ->todoModelMock
            ->shouldReceive('delete')
            ->andReturnTrue();

        // Buat instance controller
        $controller = new TodoController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDelete($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Todo yang dipilih berhasil dihapus.', $response->getSession()->get('success'));
    }
}
