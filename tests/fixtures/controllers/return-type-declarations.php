<?php

namespace App\Http\Controllers;

use App\Http\Requests\TermStoreRequest;
use App\Http\Requests\TermUpdateRequest;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $terms = Term::all();

        return view('term.index', compact('terms'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request): \Illuminate\View\View
    {
        return view('term.create');
    }

    /**
     * @param \App\Http\Requests\TermStoreRequest $request
     * @return \Illuminate\Routing\Redirector
     */
    public function store(TermStoreRequest $request): \Illuminate\Routing\Redirector
    {
        $term = Term::create($request->validated());

        $request->session()->flash('term.id', $term->id);

        return redirect()->route('term.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Term $term
     * @return \Illuminate\View\View
     */
    public function show(Request $request, Term $term): \Illuminate\View\View
    {
        return view('term.show', compact('term'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Term $term
     * @return \Illuminate\View\View
     */
    public function edit(Request $request, Term $term): \Illuminate\View\View
    {
        return view('term.edit', compact('term'));
    }

    /**
     * @param \App\Http\Requests\TermUpdateRequest $request
     * @param \App\Models\Term $term
     * @return \Illuminate\Routing\Redirector
     */
    public function update(TermUpdateRequest $request, Term $term): \Illuminate\Routing\Redirector
    {
        $term->update($request->validated());

        $request->session()->flash('term.id', $term->id);

        return redirect()->route('term.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Term $term
     * @return \Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, Term $term): \Illuminate\Routing\Redirector
    {
        $term->delete();

        return redirect()->route('term.index');
    }
}
