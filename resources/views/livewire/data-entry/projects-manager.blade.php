<?php

use Livewire\Volt\Component;
use App\Models\Project;
use Illuminate\Validation\Rule;

new class extends Component {
    public $projects;
    public $projectId = null;
    public string $projectName = '';
    public string $projectCode = '';
    public bool $isEditMode = false;

    public function mount()
    {
        $this->loadProjects();
    }

    public function loadProjects()
    {
        $this->projects = Project::orderBy('created_at', 'desc')->get();
    }

    public function save()
    {
        $this->validate([
            'projectName' => 'required|string|max:255',
            'projectCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'project_code')->ignore($this->projectId),
            ],
        ]);

        Project::updateOrCreate(
            ['id' => $this->projectId],
            [
                'project' => $this->projectName,
                'project_code' => $this->projectCode
            ]
        );

        session()->flash('message', $this->isEditMode ? 'Project Updated Successfully.' : 'Project Created Successfully.');

        $this->resetForm();
        $this->loadProjects();
    }

    public function edit($id)
    {
        $projectData = Project::findOrFail($id);
        $this->projectId = $projectData->id;
        $this->projectName = $projectData->project;
        $this->projectCode = $projectData->project_code;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        try {
            Project::findOrFail($id)->delete();
            session()->flash('message', 'Project Deleted Successfully.');
            $this->loadProjects();
        } catch (\Illuminate\Database\QueryException $e) {
            session()->flash('error', 'Cannot delete this project because it is being used in transactions.');
        }
    }

    public function resetForm()
    {
        $this->reset(['projectId', 'projectName', 'projectCode', 'isEditMode']);
        $this->resetValidation();
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            {{ $isEditMode ? 'Edit Project' : 'Add New Project' }}
        </h3>

        @if (session()->has('message'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Project Name</label>
                <input type="text" wire:model="projectName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. Malaria Control Project">
                @error('projectName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Project Code</label>
                <input type="text" wire:model="projectCode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="e.g. PC-012">
                <span class="text-xs text-gray-500 mt-1 block">Must be unique.</span>
                @error('projectCode') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg w-full transition text-sm">
                    {{ $isEditMode ? 'Update Project' : 'Save Project' }}
                </button>
                @if($isEditMode)
                    <button type="button" wire:click="resetForm" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition text-sm">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Project List</h3>
            <span class="text-xs font-semibold bg-blue-100 text-blue-800 py-1 px-3 rounded-full">Total: {{ $projects->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Project Code</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Project Name</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($projects as $projectData)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">{{ $projectData->project_code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $projectData->project }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $projectData->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button wire:click="delete({{ $projectData->id }})" wire:confirm="Are you sure you want to delete this project?" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                No projects found. Create one to get started!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
