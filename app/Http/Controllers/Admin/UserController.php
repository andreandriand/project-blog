<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

/**
 * Tujuan: CRUD user dari panel admin (admin-only).
 * Caller: routes/web.php grup admin -> admin.users.* -> Admin\UserController.
 * Dependensi: App\Models\User (cast 'password' => 'hashed'), Storage (disk public), Rules\Password.
 * Main Functions: index, create, store, edit, update, destroy.
 * Side Effects: DB write ke tabel users, upload avatar ke disk 'public', delete file avatar saat update/destroy.
 *
 * Catatan penting: Jangan memanggil Hash::make() pada field 'password' karena model User
 * sudah memiliki cast 'password' => 'hashed' yang otomatis mem-hash saat assignment.
 * Memanggil Hash::make() manual akan menghasilkan double-hashing dan user tidak bisa login.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('posts')->latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,author,reader',
            'bio' => 'nullable|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // NOTE: Model User punya cast 'password' => 'hashed'.
        // Jangan Hash::make() manual di sini — akan double-hash dan user tidak bisa login.

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', __('User berhasil dibuat!'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,author,reader',
            'bio' => 'nullable|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if (! empty($validated['password'])) {
            // NOTE: cast 'password' => 'hashed' di model User akan meng-hash otomatis.
            // Biarkan plaintext di sini — jangan Hash::make() manual (akan double-hash).
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', __('User berhasil diperbarui!'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('Tidak bisa menghapus akun sendiri!'));
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', __('User berhasil dihapus!'));
    }
}
