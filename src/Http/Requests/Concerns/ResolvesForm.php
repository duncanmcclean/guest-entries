<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use DuncanMcClean\GuestEntries\Form;

trait ResolvesForm
{
    private ?Form $form = null;

    public function form(): Form
    {
        return $this->form ??= $this->findForm();
    }

    private function findForm(): Form
    {
        $form = Form::find($this->route('handle'));

        abort_unless($form?->action() === $this->action, 404);

        return $form;
    }
}
