"""Crée des copies anonymisées des captures de la semaine 1 (les originaux ne sont jamais modifiés).

Zones floutées (coordonnées en pixels de l'image d'origine) :
- barre d'onglets du navigateur, avatar du profil, barre des tâches Windows ;
- adresse e-mail affichée dans la barre de débogage Symfony ;
- noms d'utilisateurs de la base de développement (en-tête, cartes, tableaux) ;
- chemin local contenant le nom du compte Windows.

Usage : python -I scripts/anonymiser_captures_s1.py
"""
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

SOURCE = Path("preuves/semaine1")
TARGET = SOURCE / "anonymise"

TABS = (0, 0, 1535, 40)
AVATAR = (1455, 44, 1500, 84)
HEADER_USER = (1360, 105, 1470, 150)
TOOLBAR_EMAIL = (280, 786, 700, 814)


def taskbar(height: int) -> tuple[int, int, int, int]:
    return (0, 816, 1535, height)


CAPTURES = {
    "essai1.png": ("S01_E01_tableau_bord_conducteur.png", [TABS, AVATAR, HEADER_USER, TOOLBAR_EMAIL, "taskbar", (585, 255, 665, 282)]),
    "essai2.png": ("S01_E02_publication_arrivee_avant_depart.png", [TABS, AVATAR, HEADER_USER]),
    "essai3.png": ("S01_E03_recherche_trajets_passes_proposes.png", [TABS, AVATAR, HEADER_USER, TOOLBAR_EMAIL, "taskbar", (312, 272, 480, 298), (312, 535, 440, 561)]),
    "essai4.png": ("S01_E04_doublon_reservation_refuse_interface.png", [TABS, AVATAR, HEADER_USER, TOOLBAR_EMAIL, (315, 272, 480, 298), (315, 535, 440, 561)]),
    "essai5.png": ("S01_E05_publication_date_passee_refusee.png", [TABS, AVATAR, HEADER_USER, TOOLBAR_EMAIL, "taskbar"]),
    "essai6.png": ("S01_E06_passager_sur_admin_403.png", [TABS, AVATAR, (298, 410, 460, 430)]),
    "essai8.png": ("S01_E08_mes_trajets_conducteur.png", [TABS, AVATAR, HEADER_USER, TOOLBAR_EMAIL, "taskbar"]),
    "essai10.png": ("S01_E10_get_last_user_email_sans_connexion.png", [TABS, AVATAR]),
    "essai11.png": ("S01_E11_admin_liste_trajets.png", [TABS, AVATAR, HEADER_USER, TOOLBAR_EMAIL, "taskbar", (680, 455, 820, 790)]),
    "essai12.png": ("S01_E12_reclamation_autrui_403.png", [TABS, AVATAR, (298, 410, 460, 430)]),
    "essai13.png": ("S01_E13_connexion_identifiants_invalides.png", [TABS, AVATAR]),
    "essai13b.png": ("S01_E13b_connexion_identifiants_invalides.png", [(1455, 0, 1500, 40)]),
}


def anonymise(name: str, target_name: str, zones: list) -> None:
    image = Image.open(SOURCE / name).convert("RGB")
    width, height = image.size
    draw = ImageDraw.Draw(image)
    for zone in zones:
        box = taskbar(height) if zone == "taskbar" else zone
        box = (max(0, box[0]), max(0, box[1]), min(width, box[2]), min(height, box[3]))
        if box[2] <= box[0] or box[3] <= box[1]:
            continue
        region = image.crop(box).filter(ImageFilter.GaussianBlur(radius=10)).filter(ImageFilter.GaussianBlur(radius=10))
        image.paste(region, box)
        draw.rectangle(box, outline=(120, 120, 120), width=1)
    image.save(TARGET / target_name, optimize=True)
    print(f"{name} -> {TARGET / target_name} ({len(zones)} zones)")


if __name__ == "__main__":
    TARGET.mkdir(exist_ok=True)
    for source_name, (target_name, zones) in CAPTURES.items():
        anonymise(source_name, target_name, zones)
