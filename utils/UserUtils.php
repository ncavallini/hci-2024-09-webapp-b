<?php
class UserUtils {
    public static function get_avatar(string $initials = null) {
        if($initials === null) {
            $initials = substr(Auth::user()['username'], 0, 2);
        }
        if(strlen($initials) > 2) {
            $initials = substr($initials, 0, 2);
        }
        $initials = strtoupper($initials);
        return "<img src='https://ui-avatars.com/api/?name=$initials&rounded=true&background=0d6efd&color=ffffff&format=svg&size=32' alt='$initials'> ";
    }

    public static function get_total_load(string $username) {
        $dbconnection = DBConnection::get_connection();
        $personal_load = 0;
        $group_load = 0;

        $sql = "SELECT SUM(t.estimated_load) FROM tasks t JOIN users u ON t.user_id = u.user_id  WHERE username = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([$username]);
        $personal_load = $stmt->fetchColumn();

        $sql = "SELECT SUM(t.estimated_load) as sum, u.username, u.user_id as user_id FROM group_tasks t JOIN users u ON t.user_id = u.user_id WHERE u.username = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([$username]);
        $group_load = $stmt->fetchColumn();

        return $personal_load + $group_load;
    }

    public static function get_max_load(): int {
        $dbconnection = DBConnection::get_connection();
        $sql = "SELECT username FROM users";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([]);
        $usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $max = 0;

        foreach($usernames as $username) {
            $max = max($max, self::get_total_load($username));
        }
        return $max;
    }

    public static function get_coins() : int {
        $dbconnection = DBConnection::get_connection();
        $sql = "SELECT coins FROM users WHERE user_id = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([Auth::user()['user_id']]);
        return $stmt->fetchColumn();
    }
}
?>