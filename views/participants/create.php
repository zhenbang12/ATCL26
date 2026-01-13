<?php
// Participant registration form
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="/participants/create">Register</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/participants/lookup">Find My QR</a>
    </li>
</ul>

<h2>Register Participant</h2>
<form method="post" action="/participants/store" class="mt-3">
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="full_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">IC / Passport No</label>
        <input
            type="text"
            name="ic_passport_no"
            class="form-control"
            placeholder="050112010285"
            pattern="^[0-9]{12}$"
        >
    </div>
    <div class="mb-3">
        <label class="form-label">Student ID</label>
        <input
            type="text"
            name="student_id"
            class="form-control"
            placeholder="25WMR09999"
            pattern="^[0-9]{2}[A-Z]{3}[0-9]{5}$"
        >
    </div>
    <div class="mb-3">
        <label class="form-label">Student email</label>
        <input
            type="email"
            name="student_email"
            class="form-control"
            placeholder="liowzb-wm23@student.tarc.edu.my"
            pattern="^[a-z0-9._%+-]+-?[a-z0-9._%+-]*@student\.tarc\.edu\.my$"
        >
    </div>
    <div class="mb-3">
        <label class="form-label">Intake</label>
        <select name="intake" class="form-select">
            <option value="">Select...</option>
            <option value="Diploma new intake">Diploma new intake</option>
            <option value="Foundation new intake">Foundation new intake</option>
            <option value="Degree from other campus">Degree from other campus</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Programme (write in)</label>
        <input
            type="text"
            name="programme_name"
            class="form-control"
            placeholder="e.g. Bachelor of Computer Science (Hons)"
        >
    </div>
    <div class="mb-3">
        <label class="form-label">Faculty</label>
        <select name="faculty" class="form-select">
            <option value="">Select...</option>
            <option value="FAFB">Faculty of Accountancy, Finance and Business (FAFB)</option>
            <option value="FOAS">Faculty of Applied Sciences (FOAS)</option>
            <option value="FOCS">Faculty of Computing and Information Technology (FOCS)</option>
            <option value="FOBE">Faculty of Built Environment (FOBE)</option>
            <option value="FOET">Faculty of Engineering and Technology (FOET)</option>
            <option value="FCCI">Faculty of Communication and Creative Industries (FCCI)</option>
            <option value="FSSH">Faculty of Social Science and Humanities (FSSH)</option>
            <option value="CPUS">Centre for Pre-University Studies (CPUS) [Foundation]</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">Select...</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
            <option value="Prefer not to say">Prefer not to say</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Contact No</label>
        <input
            type="text"
            name="contact_no"
            class="form-control"
            placeholder="0167719430 or 60167719430"
            pattern="^(0|60)[0-9]{9,10}$"
            title="Enter 10-12 digits starting with 0 or 60 (e.g., 0167719430 or 60167719430)"
        >
        <small class="form-text text-muted">Enter phone number starting with 0 or 60 (will be saved as 60XXXXXXXXX)</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Emergency contact No</label>
        <input
            type="text"
            name="emergency_contact_no"
            class="form-control"
            placeholder="0167719430 or 60167719430"
            pattern="^(0|60)[0-9]{9,10}$"
            title="Enter 10-12 digits starting with 0 or 60 (e.g., 0167719430 or 60167719430)"
        >
        <small class="form-text text-muted">Enter phone number starting with 0 or 60 (will be saved as 60XXXXXXXXX)</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Emergency contact Relationship</label>
        <select name="emergency_contact_relationship" class="form-select">
            <option value="">Select...</option>
            <option value="Parents">Parents</option>
            <option value="Spouse">Spouse</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Preferred language</label>
        <select name="preferred_language" class="form-select">
            <option value="">Select...</option>
            <option value="Mandarin">Mandarin</option>
            <option value="English">English</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
