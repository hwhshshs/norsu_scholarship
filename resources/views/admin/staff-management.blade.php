@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0">
        <div class="row">
          <div class="col-6 d-flex align-items-center">
            <h6 class="mb-0">Staff & Admin Management</h6>
          </div>
          <div class="col-6 text-end">
            <button type="button" class="btn bg-gradient-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addUserModal">
              <i class="fas fa-plus me-2"></i>New User
            </button>
          </div>
        </div>
      </div>
      <div class="card-body px-0 pt-0 pb-2">
        <div class="table-responsive p-0">
          <table class="table align-items-center mb-0 table-hover">
            <thead>
              <tr>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">User Info</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Role</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Joined Date</th>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($users as $user)
              <tr>
                <td>
                  <div class="d-flex px-3 py-1">
                    <div>
                      <div class="avatar avatar-sm me-3 bg-gradient-{{ $user->role == 'admin' ? 'info' : 'secondary' }}">
                        <span class="text-white text-xs font-weight-bold">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                      </div>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-sm font-weight-bold">{{ $user->name }}</h6>
                      <p class="text-xs text-secondary mb-0">{{ $user->email }}</p>
                    </div>
                  </div>
                </td>
                <td class="align-middle text-center text-sm">
                  <span class="badge badge-sm border {{ $user->role == 'admin' ? 'border-info text-info' : 'border-secondary text-secondary' }}" style="min-width: 70px;">
                    {{ strtoupper($user->role) }}
                  </span>
                </td>
                <td class="align-middle text-center">
                  <span class="text-secondary text-xs font-weight-bold">{{ $user->created_at->format('M d, Y') }}</span>
                </td>
                <td class="align-middle">
                  <div class="d-flex justify-content-center align-items-center">
                    <div style="width: 85px;" class="d-flex justify-content-start align-items-center gap-2">
                        <button class="btn btn-icon-only btn-rounded btn-outline-info mb-0 d-flex align-items-center justify-content-center" 
                                data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}"
                                title="Edit User">
                          <i class="fas fa-user-edit text-xs"></i>
                        </button>
                        @if($user->id !== auth()->id())
                        <form action="{{ route('admin.staff.delete', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user? This action cannot be undone.')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-icon-only btn-rounded btn-outline-danger mb-0 d-flex align-items-center justify-content-center"
                                  title="Delete User">
                            <i class="far fa-trash-alt text-xs"></i>
                          </button>
                        </form>
                        @endif
                    </div>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit User Modals -->
@foreach($users as $user)
<div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel{{ $user->id }}" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title font-weight-bolder text-info" id="editUserModalLabel{{ $user->id }}">
          <i class="fas fa-user-cog me-2"></i>Update User Account
        </h5>
        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.staff.update', $user->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-body text-start px-4">
              <div class="form-group mb-3">
                  <label class="form-control-label">Full Name</label>
                  <input type="text" name="name" class="form-control form-control-alternative" value="{{ $user->name }}" required>
              </div>
              <div class="form-group mb-3">
                  <label class="form-control-label">Email Address</label>
                  <input type="email" name="email" class="form-control form-control-alternative" value="{{ $user->email }}" required>
              </div>
              <div class="form-group mb-4">
                  <label class="form-control-label">Account Role</label>
                  <select name="role" class="form-control form-control-alternative" required>
                      <option value="staff" {{ $user->role == 'staff' ? 'selected' : '' }}>Staff (Limited Access)</option>
                      <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Administrator (Full Access)</option>
                  </select>
              </div>
              <div class="bg-gray-100 p-3 border-radius-lg">
                <p class="text-xs font-weight-bold mb-2 text-uppercase opacity-7">Security Update</p>
                <div class="form-group mb-3">
                    <label class="form-control-label">New Password</label>
                    <input type="password" name="password" class="form-control form-control-alternative" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label class="form-control-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control form-control-alternative">
                </div>
              </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
              <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn bg-gradient-info">Save Account Changes</button>
          </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title font-weight-bolder text-dark" id="addUserModalLabel">
          <i class="fas fa-user-plus me-2"></i>Register New Staff
        </h5>
        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.staff.store') }}" method="POST">
          @csrf
          <div class="modal-body text-start px-4">
              <div class="form-group mb-3">
                  <label class="form-control-label">Full Name</label>
                  <input type="text" name="name" class="form-control form-control-alternative" placeholder="e.g. John Doe" required>
              </div>
              <div class="form-group mb-3">
                  <label class="form-control-label">Email Address</label>
                  <input type="email" name="email" class="form-control form-control-alternative" placeholder="email@example.com" required>
              </div>
              <div class="form-group mb-3">
                  <label class="form-control-label">Account Role</label>
                  <select name="role" class="form-control form-control-alternative" required>
                      <option value="staff">Staff (Limited Access)</option>
                      <option value="admin">Administrator (Full Access)</option>
                  </select>
              </div>
              <hr class="horizontal dark my-4">
              <div class="form-group mb-3">
                  <label class="form-control-label">Initial Password</label>
                  <input type="password" name="password" class="form-control form-control-alternative" required>
              </div>
              <div class="form-group">
                  <label class="form-control-label">Confirm Password</label>
                  <input type="password" name="password_confirmation" class="form-control form-control-alternative" required>
              </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4">
              <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn bg-gradient-dark">Create User Account</button>
          </div>
      </form>
    </div>
  </div>
</div>

@endsection
