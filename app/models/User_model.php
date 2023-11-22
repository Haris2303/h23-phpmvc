<?php

class User_model
{

    private $db;
    private $table = [
        "user" => "tb_user",
        "amil" => "tb_amil",
        "muzakki" => "tb_muzakki"
    ];
    private $baseModel;

    // constructor
    public function __construct()
    {
        $this->db = new Database();
        $this->baseModel = new BaseModel($this->table['user']);
    }

    /**
     * |-----------------------------------------------------
     * |        GET DATA By
     * |-----------------------------------------------------
     */

    /**
     * Mengambil data pengguna berdasarkan ID pengguna.
     *
     * @param int $id_user ID pengguna yang akan dicari.
     * @return array Data pengguna yang cocok dengan ID pengguna yang diberikan.
     */
    public function getDataById(int $id_user): array
    {
        $this->baseModel->selectData(null, null, [], ["id_user = " => $id_user]);
        return $this->baseModel->fetch();
    }

    /**
     * Mengambil ID pengguna berdasarkan nama pengguna.
     *
     * @param string $username Nama pengguna yang akan dicari.
     * @return int|bool Data pengguna yang cocok dengan nama pengguna yang diberikan, atau false jika tidak ditemukan.
     */
    public function getIdByUsername(string $username): int|bool
    {
        $this->baseModel->selectData(null, 'id_user', [], ["username = " => $username]);
        return $this->baseModel->fetch()['id_user'];
    }

    /**
     * Mengambil token pengguna berdasarkan nama pengguna.
     *
     * @param string $username Nama pengguna yang akan dicari.
     * @return string Token pengguna yang cocok dengan nama pengguna yang diberikan.
     */
    public function getTokenByUsername(string $username): string
    {
        $this->baseModel->selectData(null, null, [], ["username = " => $username]);
        return $this->baseModel->fetch()['token'];
    }

    /**
     * Mengambil nama pengguna berdasarkan ID pengguna.
     * Fungsi akan mencari nama di tabel tb_admin, tb_amil, dan tb_muzakki.
     *
     * @param int $id_user ID pengguna yang akan dicari.
     * @return string|null Nama pengguna jika ditemukan, null jika tidak ditemukan.
     */
    public function getNamaByIdUser(int $id_user): string
    {
        // buat object dari base model
        $modelAdmin = new BaseModel('tb_admin');
        $modelAmil = new BaseModel('tb_amil');
        $modelMuzakki = new BaseModel('tb_muzakki');

        // cek pada tb_admin
        $modelAdmin->selectData(null, null, [], ["id_user = " => $id_user]);
        $nama = $modelAdmin->fetch()['nama'];
        if (is_string($nama)) return $nama;

        // cek pada tb_amil
        $modelAmil->selectData(null, null, [], ["id_user = " => $id_user]);
        $nama = $modelAmil->fetch()['nama'];
        if (is_string($nama)) return $nama;

        // cek pada tb_muzakki
        $modelMuzakki->selectData(null, null, [], ["id_user = " => $id_user]);
        $nama = $modelMuzakki->fetch()['nama'];
        if (is_string($nama)) return $nama;
    }

    /**
     * -----------------------------------------------------------
     *      AKTIVASI AKUN
     * -----------------------------------------------------------
     */

    /**
     * Mengaktifkan akun berdasarkan token.
     *
     * @param string $token Token yang digunakan untuk mengidentifikasi akun yang akan diaktifkan.
     * @return int Jumlah baris yang terpengaruh oleh operasi pembaruan.
     */
    public function aktivasiAkun(string $token): int
    {
        return $this->baseModel->updateData(["status_aktivasi" => "1"], ["token" => $token]);
    }

    /**
     * |----------------------------------------------------------
     * |        CHECK DATA
     * |----------------------------------------------------------
     */

    /**
     * Memeriksa apakah token ada dalam basis data.
     *
     * @param string $token Token yang akan diperiksa.
     * @return bool True jika token ditemukan, false jika tidak.
     */
    public function isToken(string $token): bool
    {
        return $this->baseModel->isData(["token" => $token]);
    }

    /**
     * Memeriksa apakah alamat email adalah unik dalam tabel tb_amil dan tb_muzakki, kecuali untuk pengguna dengan ID tertentu.
     *
     * @param string $email Alamat email yang akan diperiksa.
     * @param int $id_user ID pengguna yang akan dikecualikan dalam pemeriksaan.
     * @return bool True jika alamat email valid dan unik, false jika tidak.
     */
    public function isEmail(string $email, int $id_user): bool
    {
        $tb_amil = $this->table['amil'];
        $tb_muzakki = $this->table['muzakki'];

        $query = "SELECT email FROM $tb_amil WHERE (email = '$email' AND id_user <> $id_user)";
        $this->db->query($query);
        if (is_string($this->db->single()['email'])) return true;

        $query = "SELECT email FROM $tb_muzakki WHERE (email = '$email' AND id_user <> $id_user)";
        $this->db->query($query);
        if (is_string($this->db->single()['email'])) return true;

        return false;
    }

    /**
     * ------------------------------------------------------------------------------
     *               ACTION DATA  => CREATE | UPDATE | DELETE
     * ------------------------------------------------------------------------------
     */

    /**
     * Membuat pengguna baru dalam basis data.
     *
     * @param string $user Tipe pengguna (amil atau muzakki).
     * @param array $data Data pengguna yang akan dimasukkan.
     * @return string|int Pesan sukses, pesan kesalahan, atau jumlah baris yang terpengaruh (bergantung pada fungsi).
     */
    public function createUser(string $user, array $data)
    {
        // buat $user jadi lowercase
        $user = strtolower($user);

        // instansiasi object dari class BaseModel
        $baseModel = new BaseModel($this->table[$user]);

        // generate uuid
        $uuid = Utility::generateUUID();
        // generate token
        $token = Utility::generateToken();

        // cek user
        if ($user === 'amil') $level = '2';
        if ($user === 'muzakki') $level = '3';

        // cek username
        if ($this->baseModel->isData(["username" => $data['username']])) return 'Usename is already available!';
        // cek email
        if ($baseModel->isData(["email" => $data['email']])) return 'Email is already available!';
        // cek nohp
        if ($baseModel->isData(["nohp" => $data['nohp']])) return 'Handphone Number is already available!';

        // cek panjang password
        if (strlen($data['password'] < 8)) return 'Password Terlalu Lemah!';

        // password konfirmasi
        if ($data['password'] === $data['passConfirm']) {

            // insert data user
            $dataUser = [
                "username" => htmlspecialchars($data['username']),
                "password" => password_hash($data['password'], PASSWORD_DEFAULT),
                "token" => $token,
                "waktu_login" => date('Y-m-d H:i:s'),
                "level" => $level,
                "status_aktivasi" => '0'
            ];

            if ($this->baseModel->insertData($dataUser) > 0) {

                // get id user
                $this->baseModel->selectData(null, null, [], ["username =" => $data['username']]);
                $id_user = $this->baseModel->fetch()['id_user'];

                if ($user === 'muzakki') {
                    // insert data muzakki
                    $dataMuzakki = [
                        "uuid" => $uuid,
                        "id_user" => $id_user,
                        "nama" => htmlspecialchars($data['name']),
                        "email" => htmlspecialchars($data['email']),
                        "nohp" => $data['nohp'],
                    ];
                    return $baseModel->insertData($dataMuzakki);
                }

                if ($user === 'amil') {
                    // insert data amil
                    $dataAmil = [
                        "uuid" => $uuid,
                        "id_user" => $id_user,
                        "id_mesjid" => $data['masjid'],
                        "nama" => htmlspecialchars($data['name']),
                        "email" => htmlspecialchars($data['email']),
                        "nohp" => $data['nohp'],
                        "alamat" => htmlspecialchars($data['alamat'])
                    ];
                    return $baseModel->insertData($dataAmil);
                }
            }
        }

        return 'Konfirmasi password tidak sama!';
    }

    /**
     * Memperbarui username pengguna berdasarkan ID pengguna yang diberikan.
     *
     * @param int $id_user ID pengguna yang username-nya akan diubah.
     * @param string $username Username baru yang akan digunakan.
     * @return int|string Hasil dari operasi perubahan username. Jika berhasil, mengembalikan jumlah baris yang diubah, jika gagal, mengembalikan pesan error (string).
     */
    public function updateUsername(int $id_user, string $username): int|string
    {
        // Memeriksa apakah username sudah terdaftar di dalam database
        $cekUsername = $this->baseModel->isData(['username' => $username]);
        if ($cekUsername) {
            return 'Username sudah terdaftar!';
        }

        // Melakukan perubahan username
        $rowCount = $this->baseModel->updateData(["username" => $username], ["id_user" => $id_user]);

        // Mengatur session username
        $_SESSION['username'] = $username;

        // Mengembalikan hasil dari operasi perubahan username (jumlah baris yang diubah atau pesan error)
        return $rowCount;
    }

    /**
     * Mengupdate password pengguna berdasarkan username.
     *
     * @param string $username Username pengguna yang akan diupdate passwordnya.
     * @param array $data Data yang berisi password lama, password baru, dan konfirmasi password baru.
     * @return int|string Jumlah baris yang terpengaruh atau pesan kesalahan.
     */
    public function updatePassword(string $username, array $data): int|string
    {
        $table = $this->table['user'];

        $password               = $data['password'];
        $password_baru          = $data['password_baru'];
        $password_konfirmasi    = $data['password_konfirmasi'];

        // cek password lama
        $cek_password = "SELECT password FROM $table WHERE username = :username";
        $this->db->query($cek_password);
        $this->db->bind('username', $username);
        $pass_in_db = $this->db->single()['password'];

        if (password_verify($password, $pass_in_db)) {

            // cek konfirmasi pasword
            if ($password_baru !== $password_konfirmasi) return 'Password Konfirmasi Salah!';

            // cek length dari password
            if (strlen($password_baru) < 8) return 'Password minimal 8 karakter!';

            // encrypt pass
            $password_baru = password_hash($password_baru, PASSWORD_DEFAULT);

            // update data
            return $this->baseModel->updateData(["password" => $password_baru], ["username" => $username]);
        }

        return 'Password Salah!';
    }

    /**
     * Mengupdate password oleh admin berdasarkan username.
     *
     * @param array $data Data yang berisi username, password, dan konfirmasi password.
     * @return int|string Jumlah baris yang terpengaruh atau pesan kesalahan.
     */
    public function updatePasswordByAdmin(array $data): int|string
    {
        $table = $this->table['user'];

        // initial data
        $username     = $data['username'];
        $password     = $data['password'];
        $passConfirm  = $data['passConfirm'];

        // get id user
        $this->db->query("SELECT id_user FROM $table WHERE username = :username");
        $this->db->bind('username', $username);
        $dataUser = $this->db->single();

        // cek panjang password
        if (strlen($password) < 8) return 'Password Terlalu Lemah';

        // cek konfirmasi password
        if ($password !== $passConfirm) return 'Konfirmasi Password Tidak Sama!';

        // encrypt password
        $password = password_hash($password, PASSWORD_DEFAULT);

        // update data
        return $this->baseModel->updateData(["password" => $password], ["id_user" => $dataUser['id_user']]);
    }

    /**
     * Mengupdate data pengguna berdasarkan ID pengguna.
     *
     * @param array $data Data yang akan diupdate.
     * @param int $id_user ID pengguna yang akan diupdate datanya.
     * @return int Jumlah baris yang terpengaruh oleh operasi pembaruan.
     */
    public function updateDataById(array $data, int $id_user): int
    {
        $row_data = $this->getDataById($id_user);
        $username = (isset($data['username'])) ? $data['username'] : $row_data['username'];
        $token = (isset($data['token'])) ? $data['token'] : $row_data['token'];

        // cek username
        if (isset($data['username']) && is_int($this->getIdByUsername($data['username'])['id_user'])) return 0;

        return $this->baseModel->updateData(["username" => $username, "token" => $token], ["id_user" => $id_user]);
    }

    /**
     * Menghapus data pengguna berdasarkan token.
     *
     * @param string $token Token pengguna yang akan dihapus.
     * @return int Jumlah baris yang terpengaruh oleh operasi penghapusan.
     */
    public function deleteData(string $token): int
    {
        return $this->baseModel->deleteData(["token" => $token]);
    }
}
