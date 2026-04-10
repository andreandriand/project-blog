@extends('layouts.admin')
@section('title', 'Tambah Tag')
@section('page-title', 'Tambah Tag')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
            <form action="{{ route('admin.tags.store') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Tag *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full input-field" placeholder="Nama tag">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">Simpan</button>
                    <a href="{{ route('admin.tags.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
