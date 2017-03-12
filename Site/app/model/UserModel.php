<?php

/**
 * Created by PhpStorm.
 * User: Rickardh
 * Date: 2017-03-11
 * Time: 20:12
 */

class UserModel{

    public static function getUser($user_id = 0, $username = ""){
        $dbc = Database::getFactory()->getConnection();
        $stmt = $dbc->prepare("SELECT username, email, torrent_pass FROM User WHERE id = :user_id OR username = :username LIMIT 1");
        $stmt->execute(array(':user_id' => $user_id, ':username' => $username));
        if($user = $stmt->fetchObject()){
            return $user;
        }
        return null;
    }

    public static function userLogin($email, $password){
        $dbc = Database::getFactory()->getConnection();
        $userStmt = $dbc->prepare("SELECT username, password, email, torrent_pass FROM User WHERE email = :email LIMIT 1");
        $userStmt->execute(array(':email' => $email));
        if($user = $userStmt->fetchObject()){
            if (password_verify($password, $user->password)){
                $user->password = "";
                return $user;
            }
        }
        return false;
    }

    public static function registerUser($username, $password, $email){

    }


    /**
     * @param $username: string for username wish
     * @param $password: password hash
     * @param $email: string for email
     * @param $applyID: int for apply table row
     */
    public static function applyUser($username, $password, $email, $applyID){

    }
}