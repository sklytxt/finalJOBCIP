<?php
class UserClass {
    public $fullName, $email, $password, $location, $jobTitle, $contactNumber, $imagePath;

    public function __construct($fullName = "", $email = "", $password = "", $location = "", $jobTitle = "", $contactNumber = "", $imagePath = "") {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->password = $password;
        $this->location = $location;
        $this->jobTitle = $jobTitle;
        $this->contactNumber = $contactNumber;
        $this->imagePath = $imagePath;
    }

    public static function getUserById($id)
    {
        $conn = new mysqli("localhost", "root", "", "jobdb");
        if ($conn->connect_error) {
            die("Database connection failed");
        }
        $stmt = $conn->prepare("
            SELECT UserID, FullName, Email, JobTitle, Location, ContactNumber, ProfileImagePath, Usertype, CompanyName 
            FROM users
            WHERE UserID = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Add this helper field to the array to make UI logic easier
        if ($user && $user['Usertype'] === 'employer') {
            $user['DisplaySubtext'] = $user['CompanyName'] ?? $user['JobTitle'];
        } else {
            $user['DisplaySubtext'] = $user['JobTitle'] ?? '';
        }

        return $user;
    }

    public static function updateProfile($userId, $data, $file)
{
    $conn = new mysqli("localhost", "root", "", "jobdb");
    
    // Assign values safely, defaulting to NULL if the field wasn't sent
    $fullName = $data['fullname'];
    $email = $data['email'];
    $jobTitle = isset($data['jobtitle']) ? $data['jobtitle'] : null;
    $companyName = isset($data['companyname']) ? $data['companyname'] : null;
    $contact = $data['contact'];
    $location = $data['location'];

    $fileName = null;
    if (!empty($file['profile_image']['name'])) {
        $fileName = time() . "_" . basename($file['profile_image']['name']);
        move_uploaded_file($file['profile_image']['tmp_name'], "../uploads/" . $fileName);
    }

    if ($fileName) {
        $stmt = $conn->prepare("UPDATE users SET FullName=?, Email=?, JobTitle=?, CompanyName=?, ContactNumber=?, Location=?, ProfileImagePath=? WHERE UserID=?");
        $stmt->bind_param("sssssssi", $fullName, $email, $jobTitle, $companyName, $contact, $location, $fileName, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET FullName=?, Email=?, JobTitle=?, CompanyName=?, ContactNumber=?, Location=? WHERE UserID=?");
        $stmt->bind_param("ssssssi", $fullName, $email, $jobTitle, $companyName, $contact, $location, $userId);
    }

    return $stmt->execute();
}
}