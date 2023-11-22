<?php

class Web extends Controller
{

  /**
   * Menampilkan halaman beranda (home)
   * @method index
   */
  public function index(): void
  {
    // Data yang akan digunakan dalam tampilan halaman beranda
    $data = [
      "judul" => "Halaman Home", // Judul halaman beranda
      "hello" => "Hello World"
    ];

    // Memanggil tampilan untuk menghasilkan halaman beranda
    $this->view('template/header', $data);         // Tampilan header (asumsi)
    $this->view('web/index', $data);               // Tampilan konten utama halaman beranda
    $this->view('template/footer', $data);         // Tampilan footer (asumsi)
  }
}
