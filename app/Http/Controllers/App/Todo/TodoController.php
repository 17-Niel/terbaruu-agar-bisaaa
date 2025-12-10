<?php

namespace App\Http\Controllers\App\Todo;

use App\Helper\ConstHelper;
use App\Helper\ToolsHelper;
use App\Http\Controllers\Controller;
use App\Models\TodoModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);
        $search = $request->query('search', '');
        $page = $request->query('page', 1);
        $perPage = $request->query('perPage', 5);
        if ($perPage <= 0) {
            $perPage = 5;
        }

        $perPageOptions = ConstHelper::OPTION_ROWS_PER_PAGE;

        return Inertia::render('app/todo/todo-page', [
            // LAZY: hanya dipanggil jika dibutuhkan di sisi front-end
            'todoList' => fn () => TodoModel::query()
                ->where('user_id', $auth->id)
                ->when($search, function ($query) use ($search) {
                    $lower = strtolower($search);

                    $query->where(fn ($q) => $q
                        ->whereRaw('LOWER(title) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$lower}%"]));
                })
                ->orderByDesc('created_at')
                ->paginate($perPage),
            // ALWAYS: selalu dikirim (meskipun lazy props tidak dipanggil)
            'pageName' => Inertia::always('Todo List'),
            'auth' => Inertia::always($auth),
            'isEditor' => Inertia::always($isEditor),
            'search' => Inertia::always($search),
            'page' => Inertia::always($page),
            'perPage' => Inertia::always($perPage),
            'perPageOptions' => Inertia::always($perPageOptions),
        ]);
    }

    public function postChange(Request $request)
    {
        // Cek izin
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);
        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah todo.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'isDone' => 'required|boolean',
        ]);

        if (isset($request->todoId) && ! empty($request->todoId)) {
            $todo = TodoModel::where('id', $request->todoId)->where('user_id', $auth->id)->first();
            if (! $todo) {
                return back()->with('error', 'Todo tidak ditemukan.');
            }

            // Update todo
            $todo->title = $request->title;
            $todo->description = $request->description;
            $todo->is_done = $request->isDone;
            $todo->save();

            return back()->with('success', 'Todo berhasil diperbarui.');
        } else {
            // Simpan todo baru
            TodoModel::create([
                'id' => ToolsHelper::generateId(),
                'user_id' => $auth->id,
                'title' => $request->title,
                'description' => $request->description,
                'is_done' => $request->isDone,
            ]);

            return back()->with('success', 'Todo berhasil ditambahkan.');
        }
    }

    public function postDelete(Request $request)
    {
        // Cek izin
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);
        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah todo.');
        }

        $request->validate([
            'todoIds' => 'required',
        ]);

        // Hapus akses
        TodoModel::whereIn('id', $request->todoIds)->where('user_id', $auth->id)->delete();

        return back()->with('success', 'Todo yang dipilih berhasil dihapus.');
    }

    private function checkIsEditor($auth)
    {
        if (ToolsHelper::checkRoles('Todo', $auth->akses)) {
            return true;
        }

        return false;
    }
}
