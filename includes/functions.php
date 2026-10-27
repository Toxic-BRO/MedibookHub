<?php


declare(strict_types=1);


function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_language(): string
{
    return ($_SESSION['language'] ?? 'bn') === 'en' ? 'en' : 'bn';
}

function app_translation(string $text): string
{
    static $translations = [
        'Dashboard' => 'ড্যাশবোর্ড',
        'Find a doctor' => 'ডাক্তার খুঁজুন',
        'My appointments' => 'আমার অ্যাপয়েন্টমেন্ট',
        'Prescriptions' => 'প্রেসক্রিপশন',
        'Complaints' => 'অভিযোগ',
        'My profile' => 'আমার প্রোফাইল',
        'Appointments' => 'অ্যাপয়েন্টমেন্ট',
        'Calendar' => 'ক্যালেন্ডার',
        'My patients' => 'আমার রোগীরা',
        'Availability' => 'সময়সূচি',
        'Doctors' => 'ডাক্তার',
        'Users' => 'ব্যবহারকারী',
        'Reports' => 'রিপোর্ট',
        'Public site' => 'ওয়েবসাইট',
        'Sign out' => 'লগ আউট',
        'Notifications' => 'নোটিফিকেশন',
        'Mark all read' => 'সব পড়া হয়েছে হিসেবে চিহ্নিত করুন',
        'Nothing here yet.' => 'এখনও কিছু নেই।',
        'Search' => 'খুঁজুন',
        'Toggle navigation' => 'মেনু খুলুন',
        'Menu' => 'মেনু',
        'Close' => 'বন্ধ করুন',
        'Clear filters' => 'ফিল্টার মুছুন',
        'Show all doctors' => 'সব ডাক্তার দেখুন',
        'No doctors matched that search' => 'আপনার খোঁজের সঙ্গে মেলে এমন ডাক্তার পাওয়া যায়নি',
        'Try a different specialty, or clear the filters to see everyone.' => 'অন্য বিশেষজ্ঞতা দিয়ে খুঁজুন অথবা সব ডাক্তার দেখতে ফিল্টার মুছে দিন।',
        'Browse all' => 'সব দেখুন',
        'Top-rated doctors' => 'সর্বোচ্চ রেটিংপ্রাপ্ত ডাক্তার',
        'Verified accounts only — pending applications never show up here.' => 'শুধু যাচাইকৃত ডাক্তারদের দেখানো হচ্ছে।',
        'No verified doctors yet' => 'এখনও কোনো যাচাইকৃত ডাক্তার নেই',
        'An administrator needs to verify a doctor account first.' => 'প্রথমে প্রশাসককে একজন ডাক্তারের অ্যাকাউন্ট যাচাই করতে হবে।',
        'Browse by specialty' => 'বিশেষজ্ঞতা অনুযায়ী খুঁজুন',
        'Are you a doctor?' => 'আপনি কি ডাক্তার?',
        'Join as a doctor' => 'ডাক্তার হিসেবে যোগ দিন',
        'Home' => 'হোম',
        'Sign in' => 'লগ ইন',
        'Create an account' => 'অ্যাকাউন্ট তৈরি করুন',
        'Create your account' => 'আপনার অ্যাকাউন্ট তৈরি করুন',
        'Get started' => 'শুরু করুন',
        'My dashboard' => 'আমার ড্যাশবোর্ড',
        'How it works' => 'যেভাবে কাজ করে',
        'Welcome back' => 'আবারও স্বাগতম',
        'Sign in to manage your appointments.' => 'আপনার অ্যাপয়েন্টমেন্ট পরিচালনা করতে লগ ইন করুন।',
        'Email address' => 'ইমেইল ঠিকানা',
        'Password' => 'পাসওয়ার্ড',
        'Show password' => 'পাসওয়ার্ড দেখুন',
        'Hide password' => 'পাসওয়ার্ড লুকান',
        'New here?' => 'নতুন ব্যবহারকারী?',
        'or' => 'অথবা',
        'Cancel' => 'বাতিল',
        'Save changes' => 'পরিবর্তন সংরক্ষণ করুন',
        'Save' => 'সংরক্ষণ করুন',
        'Update' => 'আপডেট',
        'Edit' => 'সম্পাদনা',
        'Delete' => 'মুছুন',
        'View' => 'দেখুন',
        'Submit' => 'জমা দিন',
        'Publish review' => 'রিভিউ প্রকাশ করুন',
        'Rate your visit' => 'আপনার ভিজিটের রেটিং দিন',
        'Your feedback helps other patients choose well.' => 'আপনার মতামত অন্য রোগীদের সঠিক ডাক্তার বেছে নিতে সাহায্য করে।',
        'How was your visit?' => 'আপনার ভিজিট কেমন ছিল?',
        'Anything to add?' => 'আর কিছু জানাতে চান?',
        '(optional)' => '(ঐচ্ছিক)',
        'No reviews yet.' => 'এখনও কোনো রিভিউ নেই।',
        'Location not set' => 'ঠিকানা দেওয়া হয়নি',
        'per visit' => 'প্রতি ভিজিট',
        'Consultation fee' => 'পরামর্শ ফি',
        'per appointment' => 'প্রতি অ্যাপয়েন্টমেন্ট',
        'years experience' => 'বছরের অভিজ্ঞতা',
        'Verified doctor' => 'যাচাইকৃত ডাক্তার',
        'View profile & book' => 'প্রোফাইল দেখুন ও অ্যাপয়েন্টমেন্ট নিন',
        'View slots' => 'সময় দেখুন',
        'Book appointment' => 'অ্যাপয়েন্টমেন্ট নিন',
        'No appointments yet.' => 'এখনও কোনো অ্যাপয়েন্টমেন্ট নেই।',
        'Upcoming appointments' => 'আসন্ন অ্যাপয়েন্টমেন্ট',
        'Past appointments' => 'আগের অ্যাপয়েন্টমেন্ট',
        'Completed' => 'সম্পন্ন',
        'Pending' => 'অপেক্ষমাণ',
        'Confirmed' => 'নিশ্চিত',
        'Rejected' => 'বাতিল করা হয়েছে',
        'Cancelled' => 'বাতিল',
        'Patient' => 'রোগী',
        'Doctor' => 'ডাক্তার',
        'Admin' => 'প্রশাসক',
        'Patient menu' => 'রোগীর মেনু',
        'Doctor menu' => 'ডাক্তারের মেনু',
        'Admin menu' => 'প্রশাসকের মেনু',
        'Name' => 'নাম',
        'Phone' => 'ফোন',
        'Phone number' => 'ফোন নম্বর',
        'Address' => 'ঠিকানা',
        'Full name' => 'পূর্ণ নাম',
        'Age' => 'বয়স',
        'Gender' => 'লিঙ্গ',
        'Specialty' => 'বিশেষজ্ঞতা',
        'Location' => 'চেম্বারের ঠিকানা',
        'Fee' => 'ফি',
        'Experience' => 'অভিজ্ঞতা',
        'Search doctors' => 'ডাক্তার খুঁজুন',
        'All specialties' => 'সব বিশেষজ্ঞতা',
        'Any location' => 'যেকোনো এলাকা',
        'No results found.' => 'কোনো ফলাফল পাওয়া যায়নি।',
        'Are you sure?' => 'আপনি কি নিশ্চিত?',
        'Yes' => 'হ্যাঁ',
        'No' => 'না',
        'Loading…' => 'লোড হচ্ছে…',
        'Working…' => 'কাজ চলছে…',
        'Create your account' => 'আপনার অ্যাকাউন্ট তৈরি করুন',
        'Patients book instantly. Doctors go live after a quick verification.' => 'রোগীরা সরাসরি অ্যাপয়েন্টমেন্ট নিতে পারেন। যাচাই শেষে ডাক্তারদের প্রোফাইল চালু হয়।',
        'At least 8 characters, with a letter and a number.' => 'কমপক্ষে ৮ অক্ষর দিন, যেখানে অন্তত একটি বর্ণ ও একটি সংখ্যা থাকবে।',
        'Confirm password' => 'পাসওয়ার্ড নিশ্চিত করুন',
        'Confirm new password' => 'নতুন পাসওয়ার্ড নিশ্চিত করুন',
        'Prefer not to say' => 'উল্লেখ করতে চাই না',
        'Male' => 'পুরুষ',
        'Female' => 'নারী',
        'Other' => 'অন্যান্য',
        "I'm a patient" => 'আমি একজন রোগী',
        "I'm a doctor" => 'আমি একজন ডাক্তার',
        'Doctor accounts stay' => 'ডাক্তারের অ্যাকাউন্ট',
        'pending' => 'যাচাইয়ের অপেক্ষায়',
        'until an administrator verifies them. You can sign in and set up your schedule right away.' => 'প্রশাসক যাচাই না করা পর্যন্ত থাকবে। আপনি এখনই লগ ইন করে সময়সূচি তৈরি করতে পারবেন।',
        'Choose…' => 'বেছে নিন…',
        'Years exp.' => 'অভিজ্ঞতা (বছর)',
        'Practice location' => 'চেম্বারের ঠিকানা',
        'Short bio' => 'সংক্ষিপ্ত পরিচিতি',
        'What do you treat? How do you work?' => 'আপনি কোন রোগের চিকিৎসা করেন? আপনার চিকিৎসা পদ্ধতি কেমন?',
        'City, State' => 'শহর, জেলা',
        '+1 555 0100' => '+৮৮০ ১XXXXXXXXX',
        'Name or email' => 'নাম বা ইমেইল',
        'Name, email, specialty' => 'নাম, ইমেইল বা বিশেষজ্ঞতা',
        'Patient name' => 'রোগীর নাম',
        'Findings, diagnosis, advice…' => 'পর্যবেক্ষণ, রোগ নির্ণয়, পরামর্শ…',
        'One medicine per line, with dose and duration' => 'প্রতি লাইনে একটি ওষুধ লিখুন, সঙ্গে মাত্রা ও সময়কাল',
        'What was done about it?' => 'এ বিষয়ে কী ব্যবস্থা নেওয়া হয়েছে?',
        'Name, specialty or condition' => 'নাম, বিশেষজ্ঞতা বা সমস্যার ধরন',
        'Search' => 'খুঁজুন',
        'Previous month' => 'আগের মাস',
        'Next month' => 'পরের মাস',
        'Remove' => 'সরান',
        'Already booked' => 'ইতিমধ্যে বুক করা হয়েছে',
        'No doctors match this filter.' => 'এই ফিল্টারে কোনো ডাক্তার পাওয়া যায়নি।',
        'Total' => 'মোট',
        'Active' => 'সক্রিয়',
        'Blocked' => 'ব্লক করা',
        'Open' => 'খোলা',
        'In review' => 'পর্যালোচনাধীন',
        'Resolved' => 'সমাধান হয়েছে',
        'Actions' => 'কার্যক্রম',
        'Total appointments' => 'মোট অ্যাপয়েন্টমেন্ট',
        'Completion rate' => 'সম্পন্নের হার',
        'Active patients' => 'সক্রিয় রোগী',
        'Active doctors' => 'সক্রিয় ডাক্তার',
        'Total users' => 'মোট ব্যবহারকারী',
        'Total doctors' => 'মোট ডাক্তার',
        'Total patients' => 'মোট রোগী',
        'Reason for visit' => 'ভিজিটের কারণ',
        'Doctor profile' => 'ডাক্তারের প্রোফাইল',
        'Appointment details' => 'অ্যাপয়েন্টমেন্টের বিবরণ',
        'Book a visit' => 'অ্যাপয়েন্টমেন্ট নিন',
        'Request appointment' => 'অ্যাপয়েন্টমেন্টের অনুরোধ করুন',
        'Select a date' => 'তারিখ নির্বাচন করুন',
        'No available slots' => 'কোনো সময় খালি নেই',
        'No slots available on this day.' => 'এই দিনে কোনো সময় খালি নেই।',
        'Appointment booked successfully.' => 'অ্যাপয়েন্টমেন্টের অনুরোধ পাঠানো হয়েছে।',
        'Appointment cancelled.' => 'অ্যাপয়েন্টমেন্ট বাতিল করা হয়েছে।',
        'No appointments in this period.' => 'এই সময়ে কোনো অ্যাপয়েন্টমেন্ট নেই।',
        'Appointment history' => 'অ্যাপয়েন্টমেন্টের ইতিহাস',
        'All' => 'সব',
        'Upcoming' => 'আসন্ন',
        'Past' => 'আগের',
        'Today' => 'আজ',
        'Yesterday' => 'গতকাল',
        'Tomorrow' => 'আগামীকাল',
        'This month' => 'এই মাস',
        'This week' => 'এই সপ্তাহ',
        'Contact details' => 'যোগাযোগের তথ্য',
        'Account status' => 'অ্যাকাউন্টের অবস্থা',
        'Member since' => 'সদস্য হয়েছেন',
        'Active' => 'সক্রিয়',
        'Patient details' => 'রোগীর তথ্য',
        'Doctor details' => 'ডাক্তারের তথ্য',
        'Appointment date' => 'অ্যাপয়েন্টমেন্টের তারিখ',
        'Appointment time' => 'অ্যাপয়েন্টমেন্টের সময়',
        'No reviews yet' => 'এখনও কোনো রিভিউ নেই',
        'No appointments yet' => 'এখনও কোনো অ্যাপয়েন্টমেন্ট নেই',
        'No complaints yet' => 'এখনও কোনো অভিযোগ নেই',
        'No patients yet' => 'এখনও কোনো রোগী নেই',
        'No doctors yet' => 'এখনও কোনো ডাক্তার নেই',
        'Create account' => 'অ্যাকাউন্ট তৈরি করুন',
        'Already registered?' => 'আগে থেকেই অ্যাকাউন্ট আছে?',
        'Profile details' => 'প্রোফাইলের তথ্য',
        'Profile photo' => 'প্রোফাইল ছবি',
        'JPG, PNG or WebP, up to' => 'JPG, PNG বা WebP, সর্বোচ্চ',
        'Square images look best.' => 'বর্গাকার ছবি সবচেয়ে ভালো দেখায়।',
        'Email' => 'ইমেইল',
        'Plain language works best. Patients read this before booking.' => 'সহজ ভাষায় লিখুন। রোগীরা অ্যাপয়েন্টমেন্টের আগে এটি পড়বেন।',
        'Save profile' => 'প্রোফাইল সংরক্ষণ করুন',
        'Verification' => 'যাচাইয়ের অবস্থা',
        'Verified' => 'যাচাইকৃত',
        'Rating' => 'রেটিং',
        'Change password' => 'পাসওয়ার্ড পরিবর্তন',
        'Current password' => 'বর্তমান পাসওয়ার্ড',
        'New password' => 'নতুন পাসওয়ার্ড',
        'Confirm' => 'নিশ্চিত করুন',
        'Update password' => 'পাসওয়ার্ড আপডেট করুন',
        'No patients yet' => 'এখনও কোনো রোগী নেই',
        'visits' => 'টি ভিজিট',
        'Visit history' => 'ভিজিটের ইতিহাস',
        'Notes:' => 'নোট:',
        'Your account is pending verification' => 'আপনার অ্যাকাউন্ট যাচাইয়ের অপেক্ষায়',
        'Set availability' => 'সময়সূচি নির্ধারণ করুন',
        'Complete profile' => 'প্রোফাইল সম্পূর্ণ করুন',
        'Awaiting your answer' => 'আপনার উত্তরের অপেক্ষায়',
        'Upcoming' => 'আসন্ন',
        'Patients seen' => 'দেখা রোগী',
        'Today ·' => 'আজ ·',
        'booked' => 'টি বুকড',
        'Nothing on the books today.' => 'আজ কোনো অ্যাপয়েন্টমেন্ট নেই।',
        'Time' => 'সময়',
        'Reason' => 'কারণ',
        'Status' => 'অবস্থা',
        'Booking requests' => 'অ্যাপয়েন্টমেন্টের অনুরোধ',
        'No requests waiting. All caught up.' => 'কোনো অনুরোধ অপেক্ষায় নেই। সব আপডেট করা হয়েছে।',
        'Accept' => 'গ্রহণ করুন',
        'Reject' => 'প্রত্যাখ্যান করুন',
        'Your practice' => 'আপনার চেম্বার',
        'Weekly availability' => 'সাপ্তাহিক সময়সূচি',
        'Add hours' => 'সময় যোগ করুন',
        'Closed' => 'বন্ধ',
        'Today' => 'আজ',
        'Cancelled appointments are hidden from the calendar.' => 'বাতিল হওয়া অ্যাপয়েন্টমেন্ট ক্যালেন্ডারে দেখানো হয় না।',
        'Add a window' => 'সময় যোগ করুন',
        'Weekday' => 'বার',
        'Sunday' => 'রবিবার',
        'From' => 'শুরু',
        'Slot length' => 'প্রতি স্লটের সময়',
        'minutes' => 'মিনিট',
        'Patients see one button per slot inside the window.' => 'এই সময়ের প্রতিটি স্লট রোগীরা আলাদা বোতাম হিসেবে দেখবেন।',
        'slots / week' => 'টি স্লট / সপ্তাহ',
        'across' => 'মোট',
        'windows' => 'টি সময়সীমায়',
        'Your week' => 'আপনার সাপ্তাহিক সময়সূচি',
        'Note:' => 'নোট:',
        'This doctor is not available' => 'এই ডাক্তার বর্তমানে উপলভ্য নন',
        'The profile may have been removed, or the account is still awaiting verification.' => 'প্রোফাইলটি সরিয়ে ফেলা হয়েছে অথবা অ্যাকাউন্টটি এখনও যাচাইয়ের অপেক্ষায় আছে।',
        'Browse other doctors' => 'অন্য ডাক্তার খুঁজুন',
        'About' => 'পরিচিতি',
        'Patient reviews' => 'রোগীদের রিভিউ',
        'No reviews yet. Be the first after your visit.' => 'এখনও কোনো রিভিউ নেই। ভিজিটের পর আপনিই প্রথম রিভিউ দিতে পারেন।',
        'Book an appointment' => 'অ্যাপয়েন্টমেন্ট নিন',
        'Choose a day' => 'একটি দিন বেছে নিন',
        'Reason for visit' => 'ভিজিটের কারণ',
        'Optional, but it helps the doctor prepare.' => 'ঐচ্ছিক, তবে এটি ডাক্তারকে প্রস্তুতি নিতে সাহায্য করবে।',
        'Selected slot' => 'নির্বাচিত সময়',
        'Sign in as a patient' => 'রোগী হিসেবে লগ ইন করুন',
        'Doctor’s notes' => 'ডাক্তারের নোট',
        'Doctor\'s notes' => 'ডাক্তারের নোট',
        'No prescriptions yet' => 'এখনও কোনো প্রেসক্রিপশন নেই',
        'Book a visit' => 'অ্যাপয়েন্টমেন্ট নিন',
        'File a complaint' => 'অভিযোগ জানান',
        'About a doctor' => 'কোনো ডাক্তারের বিরুদ্ধে',
        'Not about a specific doctor' => 'নির্দিষ্ট কোনো ডাক্তারের বিরুদ্ধে নয়',
        'What happened?' => 'কী ঘটেছে?',
        'Minimum 10 characters. Only administrators can read this.' => 'কমপক্ষে ১০টি অক্ষর লিখুন। এটি শুধু প্রশাসক দেখতে পারবেন।',
        'Submit complaint' => 'অভিযোগ জমা দিন',
        'Your complaints' => 'আপনার অভিযোগগুলো',
        'No complaints filed. Hopefully it stays that way.' => 'কোনো অভিযোগ করা হয়নি। আশা করি এমনই থাকবে।',
        'Admin reply' => 'প্রশাসকের উত্তর',
        'Nothing to triage' => 'পর্যালোচনার জন্য কিছু নেই',
        'No complaints in this tab.' => 'এই ট্যাবে কোনো অভিযোগ নেই।',
        'Reply to patient' => 'রোগীকে উত্তর দিন',
        'Appts' => 'অ্যাপয়েন্টমেন্ট',
        'Actions' => 'কার্যক্রম',
        'Blocked' => 'ব্লক করা',
        'Verify' => 'যাচাই করুন',
        'Unverify' => 'যাচাই বাতিল করুন',
        'Re-verify' => 'আবার যাচাই করুন',
        'No doctors match this filter.' => 'এই ফিল্টারে কোনো ডাক্তার পাওয়া যায়নি।',
        'Filter' => 'ফিল্টার',
        'User' => 'ব্যবহারকারী',
        'Role' => 'ভূমিকা',
        'Joined' => 'যোগদানের তারিখ',
        'Bookings' => 'বুকিং',
        'Protected' => 'সুরক্ষিত',
        'Unblock' => 'আনব্লক',
        'Block' => 'ব্লক',
        'No users match this filter.' => 'এই ফিল্টারে কোনো ব্যবহারকারী পাওয়া যায়নি।',
        'Total appointments' => 'মোট অ্যাপয়েন্টমেন্ট',
        'Completion rate' => 'সম্পন্নের হার',
        'Completed visit value' => 'সম্পন্ন ভিজিটের মোট ফি',
        'Active doctors (30d)' => 'সক্রিয় ডাক্তার (৩০ দিন)',
        'Appointments · last 6 months' => 'অ্যাপয়েন্টমেন্ট · গত ৬ মাস',
        'Status breakdown' => 'অবস্থার সারসংক্ষেপ',
        'New users (30d)' => 'নতুন ব্যবহারকারী (৩০ দিন)',
        'Verified doctors' => 'যাচাইকৃত ডাক্তার',
        'Reviews' => 'রিভিউ',
        'Busiest doctors' => 'সবচেয়ে ব্যস্ত ডাক্তার',
        'No appointments yet.' => 'এখনও কোনো অ্যাপয়েন্টমেন্ট নেই।',
        'Active patients' => 'সক্রিয় রোগী',
        'Appointments total' => 'মোট অ্যাপয়েন্টমেন্ট',
        'Review' => 'পর্যালোচনা',
        'Doctor applications' => 'ডাক্তারের আবেদন',
        'All doctors' => 'সব ডাক্তার',
        'No applications waiting. The queue is clear.' => 'কোনো আবেদন অপেক্ষায় নেই।',
        'Latest bookings' => 'সর্বশেষ বুকিং',
        'When' => 'সময়',
        'No bookings yet.' => 'এখনও কোনো বুকিং নেই।',
        'Needs attention' => 'যেসব বিষয়ে নজর দিতে হবে',
        'Open complaints' => 'খোলা অভিযোগ',
        'Pending doctors' => 'যাচাইয়ের অপেক্ষায় থাকা ডাক্তার',
        'Awaiting confirmation' => 'নিশ্চিতকরণের অপেক্ষায়',
        'Top rated doctors' => 'সর্বোচ্চ রেটিংপ্রাপ্ত ডাক্তার',
        'No patients yet' => 'এখনও কোনো রোগী নেই',
        'No patients found.' => 'কোনো রোগী পাওয়া যায়নি।',
        'No appointments found.' => 'কোনো অ্যাপয়েন্টমেন্ট পাওয়া যায়নি।',
        'Patient reviews' => 'রোগীদের রিভিউ',
        'Busiest doctors' => 'সবচেয়ে ব্যস্ত ডাক্তার',
        'This week' => 'এই সপ্তাহ',
        'Monthly' => 'মাসিক',
        'Today’s notes' => 'আজকের নোট',
        'Manage all' => 'সব পরিচালনা করুন',
        'Manage' => 'পরিচালনা করুন',
        'View all' => 'সব দেখুন',
        'List view' => 'তালিকা দেখুন',
        'Back to dashboard' => 'ড্যাশবোর্ডে ফিরে যান',
        'View profile' => 'প্রোফাইল দেখুন',
        'Find doctors' => 'ডাক্তার খুঁজুন',
        'No appointments found.' => 'কোনো অ্যাপয়েন্টমেন্ট পাওয়া যায়নি।',
        'No patients found.' => 'কোনো রোগী পাওয়া যায়নি।',
        'No doctors found.' => 'কোনো ডাক্তার পাওয়া যায়নি।',
        'No users found.' => 'কোনো ব্যবহারকারী পাওয়া যায়নি।',
        'No complaints yet.' => 'এখনও কোনো অভিযোগ নেই।',
        'No prescriptions yet.' => 'এখনও কোনো প্রেসক্রিপশন নেই।',
        'No notifications yet.' => 'এখনও কোনো নোটিফিকেশন নেই।',
        'No receptionist. No middleman.' => 'মধ্যস্থতাকারী ছাড়াই সরাসরি ডাক্তার দেখান',
        'See your doctor.' => 'সরাসরি ডাক্তার দেখান।',
        'Skip the front desk.' => 'সিরিয়াল নিন সহজেই।',
        'Find a specialist, pick a slot that actually works, and book it yourself.' => 'বিশেষজ্ঞ ডাক্তার খুঁজুন, সুবিধাজনক সময় বেছে নিন এবং নিজেই অ্যাপয়েন্টমেন্ট নিন।',
        'Your doctor confirms directly — no phone tag, no waiting room queue.' => 'ডাক্তার সরাসরি আপনার অ্যাপয়েন্টমেন্ট নিশ্চিত করবেন—ফোনে যোগাযোগ বা দীর্ঘ অপেক্ষার ঝামেলা ছাড়াই।',
        'Doctor name or condition' => 'ডাক্তারের নাম বা সমস্যার ধরন',
        'All specialties' => 'সব বিশেষজ্ঞতা',
        'Any location' => 'যেকোনো এলাকা',
        'Verified doctors' => 'যাচাইকৃত ডাক্তার',
        'Appointments booked' => 'সম্পন্ন অ্যাপয়েন্টমেন্ট',
        'Average rating' => 'গড় রেটিং',
        'ডাক্তারের কাছে যাওয়ার আগে' => 'ডাক্তারের কাছে যাওয়ার আগে',
        'সচেতন থাকুন, সুস্থ থাকুন' => 'সচেতন থাকুন, সুস্থ থাকুন',
        'স্বাস্থ্য পরামর্শ' => 'স্বাস্থ্য পরামর্শ',
        'No receptionist. No middleman.' => 'মধ্যস্থতাকারী ছাড়াই সরাসরি ডাক্তার দেখান',
        'Browse by specialty' => 'বিশেষজ্ঞতা অনুযায়ী খুঁজুন',
        'How it works' => 'যেভাবে কাজ করে',
        'Four steps, zero phone calls' => 'চারটি সহজ ধাপ, ফোন করার ঝামেলা নেই',
        'The whole loop happens inside the app — including the paperwork.' => 'অ্যাপয়েন্টমেন্ট থেকে প্রয়োজনীয় তথ্য—সবকিছুই অ্যাপের মাধ্যমে।',
        'Find your doctor' => 'আপনার ডাক্তার খুঁজুন',
        'Filter by name, specialty or location. Every profile shows the fee up front.' => 'নাম, বিশেষজ্ঞতা বা এলাকা দিয়ে খুঁজুন। প্রতিটি প্রোফাইলে ফি আগে থেকেই দেখুন।',
        'Pick a real slot' => 'সুবিধাজনক সময় বেছে নিন',
        'Only future, unbooked slots are shown — generated from the doctor\'s weekly hours.' => 'ডাক্তারের সাপ্তাহিক সময়সূচি থেকে খালি ও আসন্ন সময়গুলো দেখানো হয়।',
        'Doctor confirms' => 'ডাক্তার নিশ্চিত করবেন',
        'Your request lands in their queue. Accept or reject, you get notified either way.' => 'আপনার অনুরোধ ডাক্তারের কাছে যাবে। গ্রহণ বা বাতিল—দুই ক্ষেত্রেই আপনি নোটিফিকেশন পাবেন।',
        'Notes & prescription' => 'নোট ও প্রেসক্রিপশন',
        'After the visit the doctor files notes and a prescription you can read anytime.' => 'ভিজিটের পর ডাক্তার নোট ও প্রেসক্রিপশন যোগ করবেন, যা আপনি যেকোনো সময় দেখতে পারবেন।',
        'Ready when you are.' => 'আপনি প্রস্তুত হলেই শুরু করুন।',
        'Create an account in under a minute — patients and doctors both.' => 'এক মিনিটেরও কম সময়ে রোগী বা ডাক্তার হিসেবে অ্যাকাউন্ট খুলুন।',
        'Create free account' => 'বিনামূল্যে অ্যাকাউন্ট খুলুন',
        'For doctors' => 'ডাক্তারদের জন্য',
        'Book directly. No middleman.' => 'সরাসরি অ্যাপয়েন্টমেন্ট নিন। মধ্যস্থতাকারী নেই।',
        'MediBook Hub — book appointments directly with your doctor. No receptionist, no waiting on hold.' => 'MediBook Hub — সরাসরি ডাক্তারের অ্যাপয়েন্টমেন্ট নিন। ফোনে অপেক্ষা বা মধ্যস্থতাকারীর ঝামেলা নেই।',
        'Rate your visit' => 'আপনার ভিজিটের রেটিং দিন',
        'Enter both your email and password.' => 'ইমেইল ও পাসওয়ার্ড—দুটিই লিখুন।',
        'Those credentials do not match our records.' => 'ইমেইল বা পাসওয়ার্ড সঠিক নয়।',
        'This account has been blocked. Please contact the administrator.' => 'এই অ্যাকাউন্টটি বন্ধ রাখা হয়েছে। প্রশাসকের সঙ্গে যোগাযোগ করুন।',
        'Too many failed attempts. Locked for 10 minutes.' => 'অনেকবার ভুল চেষ্টা হয়েছে। ১০ মিনিট পর আবার চেষ্টা করুন।',
        'Too many failed attempts. Try again in about ' => 'অনেকবার ভুল চেষ্টা হয়েছে। আবার চেষ্টা করুন প্রায় ',
        'minute(s).' => ' মিনিট পর।',
        'Thanks — your review is live on ' => 'ধন্যবাদ—আপনার রিভিউ প্রকাশিত হয়েছে ',
        '’s profile.' => '-এর প্রোফাইলে।',
        'Could not save the review. Please try again.' => 'রিভিউ সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।',
        'Pick a rating between 1 and 5 stars.' => '১ থেকে ৫-এর মধ্যে একটি রেটিং দিন।',
        'That appointment does not exist.' => 'অ্যাপয়েন্টমেন্টটি খুঁজে পাওয়া যায়নি।',
        'You can only review a visit after it has been completed.' => 'ভিজিট সম্পন্ন হওয়ার পরই রিভিউ দিতে পারবেন।',
        'You already reviewed this visit. Thank you.' => 'এই ভিজিটে আপনি আগেই রিভিউ দিয়েছেন। ধন্যবাদ।',
    ];

    return app_language() === 'bn' ? ($translations[$text] ?? $text) : $text;
}

function translate_html(string $html): string
{
    if (app_language() !== 'bn') {
        return $html;
    }

    $html = preg_replace_callback(
        '/(<script\b[^>]*>.*?<\/script\s*>|<style\b[^>]*>.*?<\/style\s*>|>([^<>]+)<)/is',
        static function (array $matches): string {
            if (!isset($matches[2])) {
                return $matches[0];
            }

            $text = $matches[2];
            $decoded = html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $translated = app_translation($decoded);

            if ($translated === $decoded) {
                return $matches[0];
            }

            $leading = substr($text, 0, strlen($text) - strlen(ltrim($text)));
            $trailing = substr($text, strlen(rtrim($text)));

            return '>' . $leading . e($translated) . $trailing . '<';
        },
        $html
    ) ?? $html;

    return preg_replace_callback(
        '/\b(placeholder|title|aria-label|alt|content|data-confirm)="([^"]*)"/i',
        static function (array $matches): string {
            $decoded = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $translated = app_translation($decoded);

            return $matches[1] . '="' . e($translated) . '"';
        },
        $html
    ) ?? $html;
}


function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}


function absolute_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}


function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    $alreadyBased = BASE_URL !== ''
        && ($path === BASE_URL || str_starts_with($path, BASE_URL . '/'));

    header('Location: ' . ($alreadyBased ? $path : url($path)));
    exit;
}


function dashboard_url(string $role): string
{
    return match ($role) {
        'admin'   => url('admin/dashboard.php'),
        'doctor'  => url('doctor/dashboard.php'),
        'patient' => url('patient/dashboard.php'),
        default   => url('index.php'),
    };
}


function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}


function query(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $default;
    if (!is_scalar($v)) {
        return $default;
    }
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int) $v : $default;
}

function query_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? $default;
    if (!is_scalar($v)) {
        return $default;
    }
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int) $v : $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}


function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

/** @return array<int, array{type:string,msg:string}> */
function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}



function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}


function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || $sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit(
            '<div style="font-family:system-ui;padding:2rem;max-width:560px;margin:4rem auto;'
            . 'border:1px solid #e3c2c2;border-radius:12px;background:#fdf3f3">'
            . '<h2 style="margin-top:0;color:#a33">Session expired</h2>'
            . '<p>Your form token was missing or invalid. Go back, reload the page and try again.</p>'
            . '</div>'
        );
    }
}


function auth_user(): ?array
{
    static $loaded = false;
    static $user   = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = ?');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();

    if (!$row) {          
        destroy_session();
        return null;
    }
    $user = $row;
    return $user;
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}


function require_login(string|array|null $role = null): array
{
    $user = auth_user();

    if ($user === null) {
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
        flash('warning', 'Please sign in to continue.');
        redirect('auth/login.php');
    }

    if ($user['status'] === 'blocked') {
        destroy_session();
        flash('danger', 'Your account has been blocked. Contact the clinic administrator.');
        redirect('auth/login.php');
    }

    if ($role !== null) {
        $allowed = is_array($role) ? $role : [$role];
        if (!in_array($user['role'], $allowed, true)) {
            abort_403();
        }
    }

    return $user;
}


function abort_403(string $message = 'You do not have permission to view this page.'): never
{
    http_response_code(403);
    $home = is_logged_in() ? dashboard_url(auth_user()['role']) : url('index.php');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>403 — Forbidden</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
        . '</head><body class="bg-light"><div class="container" style="max-width:560px;margin-top:12vh">'
        . '<div class="card shadow-sm border-0"><div class="card-body p-4 text-center">'
        . '<div class="display-4 mb-2">🔒</div>'
        . '<h1 class="h4">403 — Forbidden</h1>'
        . '<p class="text-muted">' . e($message) . '</p>'
        . '<a class="btn btn-primary" href="' . e($home) . '">Go to my dashboard</a>'
        . '</div></div></div></body></html>';
    exit;
}


function notify(int $userId, string $message, ?string $link = null, bool $email = true): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
    $stmt->execute([$userId, mb_substr($message, 0, 500), $link]);

    if ($email && defined('MAIL_ENABLED') && MAIL_ENABLED) {
        $u = db()->prepare('SELECT email FROM users WHERE id = ?');
        $u->execute([$userId]);
        $to = $u->fetchColumn();
        if ($to) {
            send_app_email((string) $to, APP_NAME . ' notification', $message);
        }
    }
}


function send_app_email(string $to, string $subject, string $body): bool
{
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        $line = sprintf(
            "[%s] To: %s | %s | %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_replace(["\r", "\n"], ' ', $body)
        );
        @file_put_contents(__DIR__ . '/../storage/mail.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . APP_NAME . ' <no-reply@localhost>',
    ];
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function unread_notifications(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}



function money(float|string|null $amount): string
{
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '৳';
    return $symbol . number_format((float) $amount, 2);
}


function fmt_date(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('D, d M Y', $ts) : '—';
}


function fmt_time(?string $time): string
{
    if (!$time) return '—';
    $ts = strtotime($time);
    return $ts ? date('g:i A', $ts) : '—';
}

function fmt_datetime(?string $dt): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('d M Y, g:i A', $ts) : '—';
}

function day_name(int $dow): string
{
    return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$dow] ?? '—';
}

function day_short(int $dow): string
{
    return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$dow] ?? '—';
}


function status_badge(string $status): string
{
    [$cls, $label] = match ($status) {
        'pending'   => ['warning text-dark', 'Pending'],
        'confirmed' => ['success', 'Confirmed'],
        'rejected'  => ['danger', 'Rejected'],
        'cancelled' => ['secondary', 'Cancelled'],
        'completed' => ['primary', 'Completed'],
        default     => ['light text-dark', ucfirst($status)],
    };
    return '<span class="badge rounded-pill bg-' . $cls . '">' . e($label) . '</span>';
}


function time_ago(?string $dt): string
{
    if (!$dt) return '';
    $diff = time() - strtotime($dt);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . ' min ago';
    if ($diff < 86400)   return floor($diff / 3600) . ' h ago';
    if ($diff < 604800)  return floor($diff / 86400) . ' d ago';
    return date('d M Y', strtotime($dt));
}


function generate_slots(string $start, string $end, int $minutes): array
{
    if ($minutes < 5) {
        return [];
    }

    $slots   = [];
    $cursor  = strtotime($start);
    $endTs   = strtotime($end);
    $step    = $minutes * 60;

    while ($cursor !== false && $endTs !== false && $cursor + $step <= $endTs) {
        $slots[] = date('H:i', $cursor);
        $cursor += $step;
    }
    return $slots;
}


function availability_for_day(int $doctorId, int $dow): array
{
    $stmt = db()->prepare(
        'SELECT start_time, end_time, slot_minutes
           FROM availability
          WHERE doctor_id = ? AND day_of_week = ?
          ORDER BY start_time'
    );
    $stmt->execute([$doctorId, $dow]);
    return $stmt->fetchAll();
}


function booked_slots(int $doctorId, string $date): array
{
    $stmt = db()->prepare(
        "SELECT `time`, status
           FROM appointments
          WHERE doctor_id = ?
            AND `date` = ?
            AND status IN ('pending','confirmed','completed')"
    );
    $stmt->execute([$doctorId, $date]);

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[substr((string) $row['time'], 0, 5)] = $row['status'];
    }
    return $out;
}

/**

 *
 * @return array<string, array{available:bool, booked:bool, past:bool, status:?string}>
 */
function doctor_day_slots(int $doctorId, string $date): array
{
    $dow = (int) date('w', strtotime($date));

    $grid = [];
    foreach (availability_for_day($doctorId, $dow) as $window) {
        foreach (generate_slots((string) $window['start_time'], (string) $window['end_time'], (int) $window['slot_minutes']) as $slot) {
            $grid[$slot] = true;   
        }
    }
    ksort($grid);

    $booked  = booked_slots($doctorId, $date);
    $now     = new DateTimeImmutable('now');
    $cutoff  = $now->modify('+' . SLOT_LEAD_MINUTES . ' minutes');
    $result  = [];

    foreach (array_keys($grid) as $slot) {
        $startTs = strtotime($date . ' ' . $slot);
        $isPast  = $startTs === false || $startTs <= $cutoff->getTimestamp();

        $result[$slot] = [
            'available' => !$isPast && !isset($booked[$slot]),
            'booked'    => isset($booked[$slot]),
            'past'      => $isPast,
            'status'    => $booked[$slot] ?? null,
        ];
    }
    return $result;
}


function doctor_open_days(int $doctorId, int $limit = 14): array
{
    $stmt = db()->prepare('SELECT DISTINCT day_of_week FROM availability WHERE doctor_id = ?');
    $stmt->execute([$doctorId]);
    $days = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if (!$days) {
        return [];
    }

    $out     = [];
    $today   = new DateTimeImmutable('today');
    $horizon = (int) BOOKING_HORIZON_DAYS;

    for ($i = 0; $i < $horizon && count($out) < $limit; $i++) {
        $d = $today->modify("+{$i} day");
        if (in_array((int) $d->format('w'), $days, true)) {
            $out[$d->format('Y-m-d')] = $d->format('D d M');
        }
    }
    return $out;
}


function slot_is_bookable(int $doctorId, string $date, string $time): bool
{
    $time = substr($time, 0, 5);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
        return false;
    }

    $ts = strtotime($date . ' ' . $time);
    if ($ts === false) {
        return false;
    }

    $cutoff = (new DateTimeImmutable('now'))->modify('+' . SLOT_LEAD_MINUTES . ' minutes');
    if ($ts <= $cutoff->getTimestamp()) {
        return false;
    }
    if ($ts > (new DateTimeImmutable('today'))->modify('+' . BOOKING_HORIZON_DAYS . ' days')->getTimestamp()) {
        return false;
    }

    $slots = doctor_day_slots($doctorId, $date);
    return isset($slots[$time]) && $slots[$time]['available'];
}




function search_doctors(string $q = '', string $specialty = '', string $location = '', int $limit = 60): array
{
    $sql = "SELECT u.id, u.name, d.specialty, d.fee, d.experience, d.location, d.photo,
                   (SELECT ROUND(AVG(r.rating), 1)
                      FROM reviews r
                      JOIN appointments a ON a.id = r.appointment_id
                     WHERE a.doctor_id = u.id) AS avg_rating,
                   (SELECT COUNT(*)
                      FROM reviews r
                      JOIN appointments a ON a.id = r.appointment_id
                     WHERE a.doctor_id = u.id) AS review_count
              FROM doctors d
              JOIN users u ON u.id = d.user_id
             WHERE d.is_verified = 1
               AND u.status = 'active'";

    $params = [];

    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR d.specialty LIKE ? OR d.bio LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    if ($specialty !== '') {
        $sql .= ' AND d.specialty = ?';
        $params[] = $specialty;
    }
    if ($location !== '') {
        $sql .= ' AND d.location LIKE ?';
        $params[] = '%' . $location . '%';
    }

    
    $sql .= ' ORDER BY (avg_rating IS NULL), avg_rating DESC, u.name ASC LIMIT ' . max(1, $limit);

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


function get_specialties(): array
{
    $sql = "SELECT d.specialty, COUNT(*) AS total
              FROM doctors d
              JOIN users u ON u.id = d.user_id
             WHERE d.is_verified = 1 AND u.status = 'active'
             GROUP BY d.specialty
             ORDER BY d.specialty";
    return db()->query($sql)->fetchAll();
}


function get_locations(): array
{
    $sql = "SELECT DISTINCT d.location
              FROM doctors d
              JOIN users u ON u.id = d.user_id
             WHERE d.is_verified = 1 AND u.status = 'active' AND d.location <> ''
             ORDER BY d.location";
    return db()->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}


function common_specialties(): array
{
    return [
        'General Medicine', 'Cardiology', 'Dermatology', 'Pediatrics', 'Orthopedics',
        'Neurology', 'Gynecology', 'Psychiatry', 'ENT', 'Ophthalmology',
        'Dentistry', 'Endocrinology', 'Gastroenterology', 'Pulmonology', 'Urology',
    ];
}


function doctor_detail(int $doctorId): ?array
{
    $sql = "SELECT u.id, u.name, u.email, u.status, d.specialty, d.fee, d.experience,
                   d.bio, d.location, d.photo, d.is_verified
              FROM doctors d
              JOIN users u ON u.id = d.user_id
             WHERE d.user_id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$doctorId]);
    return $stmt->fetch() ?: null;
}


function weekly_availability(int $doctorId): array
{
    $stmt = db()->prepare(
        'SELECT id, day_of_week, start_time, end_time, slot_minutes
           FROM availability WHERE doctor_id = ?
          ORDER BY day_of_week, start_time'
    );
    $stmt->execute([$doctorId]);

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[(int) $row['day_of_week']][] = $row;
    }
    return $out;
}


function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts, static fn ($p) => $p !== '' && !str_starts_with($p, 'Dr')));
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : mb_strtoupper(mb_substr($name, 0, 1));
}


function doctor_avatar(array $doctor, string $sizeClass = ''): string
{
    
    $name  = (string) ($doctor['name'] ?? $doctor['doctor_name'] ?? '?');
    $photo = $doctor['photo'] ?? null;
    $cls   = trim('avatar ' . $sizeClass);

    if (!empty($photo)) {
        return '<img class="' . e($cls) . '" src="' . e(UPLOAD_URL . '/' . $photo) . '" alt="' . e($name) . '">';
    }
    return '<div class="' . e($cls) . '">' . e(initials($name)) . '</div>';
}



function icon(string $name, int $size = 20, string $class = ''): string
{
    static $paths = [
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
        'calendar'   => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
        'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'      => '<circle cx="9" cy="8" r="3.5"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M17 11a3 3 0 1 0 0-6"/><path d="M18.5 21a6.5 6.5 0 0 0-3.2-5.6"/>',
        'bell'       => '<path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/>',
        'star'       => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        'check'      => '<path d="m4 12 5 5L20 6"/>',
        'x'          => '<path d="M6 6l12 12M18 6 6 18"/>',
        'plus'       => '<path d="M12 5v14M5 12h14"/>',
        'pencil'     => '<path d="M4 20h4L18 10l-4-4L4 16v4z"/><path d="m14 6 4 4"/>',
        'trash'      => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'logout'     => '<path d="M15 12H3"/><path d="m7 8-4 4 4 4"/><path d="M11 4h8a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-8"/>',
        'stetho'     => '<path d="M6 3v6a4 4 0 0 0 8 0V3"/><path d="M4 3h4M12 3h4"/><path d="M10 13v3a5 5 0 0 0 5 5 4 4 0 0 0 4-4v-2"/><circle cx="19" cy="11" r="2"/>',
        'shield'     => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'chart'      => '<path d="M3 21h18"/><rect x="5" y="11" width="3" height="7" rx="1"/><rect x="10.5" y="6" width="3" height="12" rx="1"/><rect x="16" y="14" width="3" height="4" rx="1"/>',
        'chat'       => '<path d="M21 12a8 8 0 0 1-8 8H7l-4 3v-5.5A8 8 0 1 1 21 12z"/>',
        'file'       => '<path d="M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7z"/><path d="M14 3v4h4"/><path d="M9 13h6M9 17h4"/>',
        'clipboard'  => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 10h6M9 14h4"/>',
        'pin'        => '<path d="M12 21s7-6.3 7-11a7 7 0 1 0-14 0c0 4.7 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'card'       => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'arrow'      => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
        'chevron'    => '<path d="m9 5 7 7-7 7"/>',
        'alert'      => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/>',
        'heart'      => '<path d="M12 20s-7-4.6-7-9.4A3.6 3.6 0 0 1 12 8a3.6 3.6 0 0 1 7 2.6C19 15.4 12 20 12 20z"/>',
        'mail'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone'      => '<path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3z"/>',
        'lock'       => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'eye'        => '<path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>',
        'grid'       => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'menu'       => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'sliders'    => '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
        'refresh'    => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
        'dot'        => '<circle cx="12" cy="12" r="3"/>',
    ];

    $body = $paths[$name] ?? $paths['dot'];
    $cls  = trim('mb-icon ' . $class);

    return '<svg class="' . e($cls) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
        . 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" '
        . 'stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}


function doctor_rating(int $doctorId): array
{
    $stmt = db()->prepare(
        'SELECT ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(*) AS total
           FROM reviews r
           JOIN appointments a ON a.id = r.appointment_id
          WHERE a.doctor_id = ?'
    );
    $stmt->execute([$doctorId]);
    $row = $stmt->fetch();

    return [
        'avg'   => $row && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : 0.0,
        'total' => (int) ($row['total'] ?? 0),
    ];
}

function star_html(float $avg, int $total, bool $showCount = true): string
{
    $full  = (int) floor($avg);
    $half  = ($avg - $full) >= 0.5;
    $html  = '<span class="text-warning" aria-hidden="true">';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $html .= '★';
        } elseif ($i === $full + 1 && $half) {
            $html .= '⯨';
        } else {
            $html .= '<span class="text-secondary opacity-50">★</span>';
        }
    }
    $html .= '</span>';

    if ($showCount) {
        $html .= $total > 0
            ? ' <span class="text-muted small">' . number_format($avg, 1) . ' (' . $total . ')</span>'
            : ' <span class="text-muted small">No reviews yet</span>';
    }
    return $html;
}
