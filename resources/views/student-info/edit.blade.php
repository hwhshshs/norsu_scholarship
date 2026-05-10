@extends('layouts.user_type.auth')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header pb-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Edit Student</h6>
        <a href="{{ url()->previous() }}" class="btn btn-icon-only btn-sm btn-outline-simple mb-0" title="Return">
            <i class="fas fa-arrow-left"></i>
        </a>
      </div>
      <div class="card-body">
        <form action="{{ route('student-info.update', $student->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Student ID No *</label>
                    <input type="text" name="student_id_no" id="student_id_no" class="form-control" placeholder="202600001" value="{{ old('student_id_no', $student->student_id_no) }}" maxlength="9" required>
                    @error('student_id_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Scholarship Program</label>
                    <select name="scholarship_program" class="form-control">
                        <option value="N/A" {{ old('scholarship_program', $student->scholarship_program) == 'N/A' ? 'selected' : '' }}>N/A (Unlocked)</option>
                        <option value="TDP-TES" {{ old('scholarship_program', $student->scholarship_program) == 'TDP-TES' ? 'selected' : '' }}>TDP-TES</option>
                        <option value="CHED" {{ old('scholarship_program', $student->scholarship_program) == 'CHED' ? 'selected' : '' }}>CHED</option>
                        <option value="ACEF-GIAHEP" {{ old('scholarship_program', $student->scholarship_program) == 'ACEF-GIAHEP' ? 'selected' : '' }}>ACEF-GIAHEP</option>
                        <option value="CMSP" {{ old('scholarship_program', $student->scholarship_program) == 'CMSP' ? 'selected' : '' }}>CMSP</option>
                    </select>
                    @error('scholarship_program') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $student->last_name) }}" required>
                    @error('last_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Given Name *</label>
                    <input type="text" name="given_name" class="form-control" value="{{ old('given_name', $student->given_name) }}" required>
                    @error('given_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Middle Initial</label>
                    <input type="text" name="middle_initial" class="form-control" value="{{ old('middle_initial', $student->middle_initial) }}">
                    @error('middle_initial') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Degree Program</label>
                    <input type="text" name="degree_program" class="form-control" list="norsu_programs" value="{{ old('degree_program', $student->degree_program) }}" placeholder="Select or type program...">
                    <datalist id="norsu_programs">
                        <option value="Bachelor of Science in Information Technology">
                        <option value="Bachelor of Science in Computer Science">
                        <option value="Bachelor of Science in Industrial Technology">
                        <option value="Bachelor of Science in Civil Engineering">
                        <option value="Bachelor of Science in Electrical Engineering">
                        <option value="Bachelor of Science in Mechanical Engineering">
                        <option value="Bachelor of Science in Electronics Engineering">
                        <option value="Bachelor of Science in Computer Engineering">
                        <option value="Bachelor of Science in Geodetic Engineering">
                        <option value="Bachelor of Science in Architecture">
                        <option value="Bachelor of Science in Accountancy">
                        <option value="Bachelor of Science in Business Administration">
                        <option value="Bachelor of Science in Office Administration">
                        <option value="Bachelor of Science in Criminology">
                        <option value="Bachelor of Science in Nursing">
                        <option value="Bachelor of Science in Pharmacy">
                        <option value="Bachelor of Secondary Education">
                        <option value="Bachelor of Elementary Education">
                        <option value="Bachelor of Physical Education">
                        <option value="Bachelor of Technology and Livelihood Education">
                        <option value="Bachelor of Arts in English">
                        <option value="Bachelor of Arts in Political Science">
                        <option value="Bachelor of Science in Biology">
                        <option value="Bachelor of Science in Chemistry">
                        <option value="Bachelor of Science in Mathematics">
                        <option value="Bachelor of Science in Geology">
                        <option value="Bachelor of Science in Hospitality Management">
                        <option value="Bachelor of Science in Tourism Management">
                    </datalist>
                    @error('degree_program') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Year Level</label>
                    <select name="year_level" class="form-control">
                        <option value="" disabled>Select Year Level</option>
                        <option value="1ST YEAR" {{ old('year_level', $student->year_level) == '1ST YEAR' ? 'selected' : '' }}>1ST YEAR</option>
                        <option value="2ND YEAR" {{ old('year_level', $student->year_level) == '2ND YEAR' ? 'selected' : '' }}>2ND YEAR</option>
                        <option value="3RD YEAR" {{ old('year_level', $student->year_level) == '3RD YEAR' ? 'selected' : '' }}>3RD YEAR</option>
                        <option value="4TH YEAR" {{ old('year_level', $student->year_level) == '4TH YEAR' ? 'selected' : '' }}>4TH YEAR</option>
                        <option value="5TH YEAR" {{ old('year_level', $student->year_level) == '5TH YEAR' ? 'selected' : '' }}>5TH YEAR</option>
                    </select>
                    @error('year_level') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">PWD No</label>
                    <input type="text" name="pwd_no" class="form-control" value="{{ old('pwd_no', $student->pwd_no) }}">
                    @error('pwd_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">IP No</label>
                    <input type="text" name="ip_no" class="form-control" value="{{ old('ip_no', $student->ip_no) }}">
                    @error('ip_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
                    @error('email') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contact No</label>
                    <input type="text" name="contact_no" class="form-control" value="{{ old('contact_no', $student->contact_no) }}">
                    @error('contact_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">FB Link</label>
                    <input type="text" name="fb_link" class="form-control" value="{{ old('fb_link', $student->fb_link) }}">
                    @error('fb_link') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-md-12 mb-3 border-top pt-3">
                    <label class="form-label font-weight-bold">TDP-TES Award No</label>
                    <input type="text" name="tdp_tes_award_no" class="form-control" value="{{ old('tdp_tes_award_no', $student->tdp_tes_award_no) }}" placeholder="Enter Award Number if applicable">
                    @error('tdp_tes_award_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update Student</button>
                <a href="{{ route('student-info.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('js')
<script>
document.getElementById('student_id_no').addEventListener('input', function (e) {
    // Remove any non-numeric characters
    this.value = this.value.replace(/[^0-9]/g, '');
    
    // Ensure it doesn't exceed 9 digits
    if (this.value.length > 9) {
        this.value = this.value.slice(0, 9);
    }
});
</script>
@endpush
