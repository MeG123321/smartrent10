<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pomoc — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .faq-item {
      background: var(--card);
      border: 1px solid rgba(255,255,255,0.03);
      border-radius: 12px;
      margin-bottom: 12px;
      overflow: hidden;
    }

    .faq-question {
      padding: 14px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--card);
      border: none;
      width: 100%;
      text-align: left;
      color: var(--text);
      font-size: 0.95rem;
      font-weight: 600;
      transition: background 0.2s;
    }

    .faq-question:hover {
      background: rgba(255,255,255,0.02);
    }

    .faq-question::after {
      content: '▼';
      font-size: 0.8rem;
      color: var(--accent-2);
      transition: transform 0.3s;
    }

    .faq-question.active::after {
      transform: rotate(180deg);
    }

    .faq-answer {
      display: none;
      padding: 14px;
      color: var(--muted);
      border-top: 1px solid rgba(255,255,255,0.03);
      font-size: 0.9rem;
      line-height: 1.5;
    }

    .faq-answer.active {
      display: block;
    }

    .contact-section {
      background: rgba(96,165,250,0.1);
      border: 1px solid rgba(96,165,250,0.2);
      border-radius: 12px;
      padding: 16px;
      margin-top: 24px;
      text-align: center;
    }

    .contact-section p {
      margin: 8px 0;
      color: var(--muted);
    }

    .contact-section .btn {
      margin-top: 8px;
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Pomoc</h2>

  <!-- Dla wynajmujących -->
  <h3 style="margin-top: 24px; color: var(--accent-2);">🏠 Wynajmujący</h3>
  
  <div class="faq-item">
    <button class="faq-question">Jak wystawić mieszkanie?</button>
    <div class="faq-answer">
      Moje konto → Moje mieszkania → Dodaj nowe. Wypełnij opis, cenę i zdjęcia.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Kiedy otrzymam płatność?</button>
    <div class="faq-answer">
      Płatności przetwarzane są co miesiąc do 5 dnia. smartrent pobiera 10% prowizji. Reszta trafia na Twoje konto bankowe.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Jak zmienić cenę mieszkania?</button>
    <div class="faq-answer">
      Moje konto → Moje mieszkania → Wybierz mieszkanie → Edytuj cenę. Zmiana dotyczy tylko nowych rezerwacji.
    </div>
  </div>

  <!-- Dla najemców -->
  <h3 style="margin-top: 24px; color: var(--accent-2);">🔑 Najemcy</h3>

  <div class="faq-item">
    <button class="faq-question">Jak wynająć mieszkanie?</button>
    <div class="faq-answer">
      Oferty → Przeszukaj i filtruj → Kliknij na mieszkanie → Wynajmij. Czekaj na potwierdzenie właściciela.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Gdzie śledzić moje rezerwacje?</button>
    <div class="faq-answer">
      Moje konto → Wynajęte mieszkania. Tam zobaczysz daty, cenę i dane właściciela.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Jak anulować rezerwację?</button>
    <div class="faq-answer">
      Moje konto → Wynajęte mieszkania → Kliknij na mieszkanie → Anuluj rezerwację. Anulowanie może wpłynąć na Twoją ocenę.
    </div>
  </div>

  <!-- Płatności -->
  <h3 style="margin-top: 24px; color: var(--accent-2);">💳 Płatności</h3>

  <div class="faq-item">
    <button class="faq-question">Jakie metody płatności są dostępne?</button>
    <div class="faq-answer">
      Karty kredytowe (Visa, Mastercard), przelewy bankowe i PayPal.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Co jeśli nie mogę zapłacić na czas?</button>
    <div class="faq-answer">
      Napisz do właściciela z wyjaśnieniem. Możliwe jest ustanowienie karencji lub płatności ratami.
    </div>
  </div>

  <!-- Bezpieczeństwo -->
  <h3 style="margin-top: 24px; color: var(--accent-2);">🔒 Bezpieczeństwo</h3>

  <div class="faq-item">
    <button class="faq-question">Jak chronić moje konto?</button>
    <div class="faq-answer">
      Używaj silnego hasła (8+ znaków), potwierdź email i włącz weryfikację dwuskładnikową w ustawieniach.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Czy moje dane są bezpieczne?</button>
    <div class="faq-answer">
      Tak. Wszystkie dane szyfrowane (SSL), zgodne z GDPR/RODO. Nigdy nie udostępniamy danych trzecim stronom.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question">Jak zgłosić oszustwa?</button>
    <div class="faq-answer">
      Napisz do support@smartrent.pl z opisem problemu i screenshotami. Podejrzane konta usuwamy natychmiast.
    </div>
  </div>

  <!-- Kontakt -->
  <div class="contact-section">
    <h3 style="margin: 0 0 8px 0;">Masz inne pytanie?</h3>
    <p>Email: <strong>support@smartrent.pl</strong></p>
    <p>Odpowiadamy w ciągu 24-48 godzin</p>
  </div>

</main>
<?php include 'includes/footer.php'; ?>
<script>
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', function() {
      const answer = this.nextElementSibling;
      document.querySelectorAll('.faq-answer.active').forEach(el => {
        el.classList.remove('active');
        el.previousElementSibling.classList.remove('active');
      });
      answer.classList.add('active');
      this.classList.add('active');
    });
  });
</script>
</body>
</html>