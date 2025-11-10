<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HotelManager – Tema 1</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      color: #333;
      margin: 40px;
      line-height: 1.6;
    }
    h1, h2 {
      color: #004080;
    }
    header {
      text-align: center;
      margin-bottom: 30px;
    }
    section {
      background: white;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 10px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 10px;
    }
    table, th, td {
      border: 1px solid #aaa;
    }
    th, td {
      padding: 8px;
      text-align: left;
    }
  </style>
</head>
<body>
  <header>
    <h1>HotelManager</h1>
  </header>

 <section>
    <h2>1. Descriere generală a aplicației</h2>
    <p>Aplicația <strong>HotelManager</strong> este o platformă web pentru gestionarea activităților unui hotel. Ea permite administrarea camerelor, rezervărilor, clienților și serviciilor suplimentare, cum ar fi curățenia. Aplicația oferă roluri diferite pentru utilizatori: <strong>client</strong>, <strong>angajat</strong> și <strong>manager</strong>, pentru a eficientiza procesul de rezervare și gestionare a hotelului.</p>
</section>

   <section>
    <h2>Arhitectura aplicației</h2>

    <p>Aplicația HotelManager gestionează mai multe entități care interacționează pentru a permite rezervarea camerelor și solicitarea serviciilor:</p>
    
    <p><strong>Client:</strong> poate face rezervări și poate solicita servicii suplimentare, precum curățenia camerei.</p>
    <p><strong>Employee (angajat):</strong> primește cereri de curățenie și marchează camerele ca finalizate.</p>
    <p><strong>Manager:</strong> aprobă sau respinge rezervările, gestionează camerele și alocă angajați pentru curățenie.</p>
    <p><strong>Cameră:</strong> poate fi rezervată de clienți și asociată cu cereri de curățenie; statusul poate fi modificat de manager.</p>
    <p><strong>Rezervare:</strong> leagă un client de o cameră pentru o anumită perioadă; rezervările sunt aprobate sau respinse de manager.</p>
    <p><strong>CleaningRequest (cerere curățenie):</strong> cererea unui client pentru curățenie; angajatul este alocat pentru realizarea acesteia și statusul se actualizează pe parcurs.</p>

    <p>Relațiile principale și fluxurile aplicației sunt următoarele:</p>
    <ul>
      <li>Un client poate avea mai multe rezervări, fiecare legată de o cameră specifică.</li>
      <li>După rezervare, clientul poate solicita curățenie; angajatul este alocat pentru realizarea acesteia.</li>
      <li>Managerul poate modifica statusul camerelor, aproba rezervări și vizualiza raportul cererilor de curățenie.</li>
      <li>Baza de date relațională păstrează toate legăturile între utilizatori, camere, rezervări și cereri de curățenie, asigurând consistența informațiilor.</li>
    </ul>
</section>

<section>
    <h2>5. Soluția de implementare</h2>
    <p>Aplicația va fi implementată folosind:</p>
    <ul>
      <li><strong>Frontend:</strong> HTML, CSS, JavaScript</li>
      <li><strong>Backend:</strong> PHP pentru autentificare, roluri și procesarea cererilor</li>
      <li><strong>Bază de date:</strong> MySQL</li>
      <li><strong>Hosting:</strong> InfinityFree (versiune gratuită)</li>
      <li><strong>Control versiuni:</strong> GitHub</li>
    </ul>
</section>

</body>
</html>
