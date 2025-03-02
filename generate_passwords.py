import os

# Настройки программы
CONFIG = {
    "specialchars": ["!", "@", "#", "$", "%", "&", "*"],
    "numfrom": 0,
    "numto": 1000,
    "leet": {
        "a": "4",
        "e": "3",
        "i": "1",
        "o": "0",
        "s": "5",
        "t": "7",
    },
    "min_length": 4,
    "max_length": 32,
}

def make_leet(word):
    for letter, leet_letter in CONFIG["leet"].items():
        word = word.replace(letter, leet_letter)
    return word

def generate_combinations(name, surname, nickname):
    combinations = set()

    base_words = []
    if name:
        base_words.extend([name, name.capitalize(), name[::-1]])
    if surname:
        base_words.extend([surname, surname.capitalize(), surname[::-1]])
    if nickname:
        base_words.extend([nickname, nickname.capitalize(), nickname[::-1]])

    for word1 in base_words:
        for word2 in base_words:
            if word1 != word2:
                combinations.add(word1 + word2)

    for word in base_words:
        for num in range(CONFIG["numfrom"], CONFIG["numto"]):
            combinations.add(word + str(num))
            combinations.add(str(num) + word)

    for word in base_words:
        for char in CONFIG["specialchars"]:
            combinations.add(word + char)
            combinations.add(char + word)

    leet_combinations = set()
    for word in combinations:
        leet_combinations.add(make_leet(word))

    combinations.update(leet_combinations)

    filtered_combinations = {
        word
        for word in combinations
        if CONFIG["min_length"] <= len(word) <= CONFIG["max_length"]
    }

    return filtered_combinations

def write_to_file(filename, combinations):
    with open(filename, "w") as f:
        f.write("\n".join(sorted(combinations)))
    print(f"[+] Сохранено {len(combinations)} уникальных паролей в файл {filename}")

def main():
    print("[+] Введите данные для генерации словаря (можно пропустить поля):")
    name = input("> Имя: ").lower().strip() or None
    surname = input("> Фамилия: ").lower().strip() or None
    nickname = input("> Никнейм: ").lower().strip() or None

    if not (name or surname or nickname):
        print("[-] Пожалуйста, введите хотя бы одно значение!")
        return

    print("[+] Генерация комбинаций...")
    combinations = generate_combinations(name, surname, nickname)

    output_filename = f"{nickname or name or 'output'}.txt"
    write_to_file(output_filename, combinations)

if __name__ == "__main__":
    main()