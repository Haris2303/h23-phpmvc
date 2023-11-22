<?php

class Flasher
{

  /**
   * @param string $pesan pesan dari sebuah flash
   * @param string $tipe terdapat beberapa tipe yaitu `success|warning|info|danger`
   * @param string $btnOK berikan charakter `'n'` tidak memunculkan tombol OK |`'y'` memunculkan tombol OK
   */
  public static function setFlash(string $pesan, string $tipe, string $btnOK = 'n'): array
  {
    return $_SESSION['flash'] = [
      'pesan' => $pesan,
      'tipe'  => $tipe,
      'btnOK' => $btnOK
    ];
  }

  // flash sweetalert
  public static function flash(): void
  {
    if (isset($_SESSION['flash'])) {
      $flash = $_SESSION['flash']['pesan'] . '|' . $_SESSION['flash']['tipe'] . '|' . $_SESSION['flash']['btnOK'];
      echo '<div class="flash" data-flash="' . $flash . '"></div>';
    }
    unset($_SESSION['flash']);
  }
}
