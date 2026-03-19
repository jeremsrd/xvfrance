# Améliorations fiche Joueur (Player)

## 1. Migration — Nouveaux champs sur la table `players`

Créer une migration `add_fields_to_players_table` :

```
cap_number          INT NULL            -- Numéro d'international (ex: 1er sélectionné = 1, Dupont = 1095e)
death_date          DATE NULL           -- Date de décès
nickname            VARCHAR(100) NULL   -- Surnom ("Casque d'Or", "le Petit Prince"...)
birth_country_id    INT FK countries NULL -- Pays de naissance (peut différer de la nationalité sportive)
```

Remplacer la colonne `photo_url` (VARCHAR) par `photo_path` (VARCHAR 255 NULL)
pour stocker le chemin du fichier uploadé dans le storage Laravel.

## 2. Photo — Upload fichier au lieu d'URL

- Utiliser le composant `FileUpload` de Filament au lieu de `TextInput`
- Stocker les photos dans `storage/app/public/players/`
- Lancer `php artisan storage:link` si pas déjà fait
- Format accepté : jpg, png, webp
- Taille max : 2 Mo
- Redimensionner à 400x400 max (optionnel)

Dans le formulaire Filament :
```php
FileUpload::make('photo_path')
    ->label('Photo')
    ->image()
    ->directory('players')
    ->maxSize(2048)
    ->imageResizeMode('cover')
    ->imageCropAspectRatio('1:1')
    ->imageResizeTargetWidth('400')
    ->imageResizeTargetHeight('400')
```

## 3. Model Player — Mettre à jour

- Ajouter `death_date`, `cap_number`, `nickname`, `birth_country_id`, `photo_path` aux $fillable
- Ajouter la relation `birthCountry()` → belongsTo Country
- Ajouter le cast `death_date` → date
- Supprimer `photo_url` des $fillable si encore présent
- Ajouter un accessor `isDeceased()` : return $this->death_date !== null
- Ajouter un accessor `fullName()` : return first_name . ' ' . last_name

## 4. PlayerResource — Formulaire amélioré

Organiser le formulaire en sections :

### Section "Identité"
- first_name (required)
- last_name (required)
- nickname
- birth_date
- death_date (affiché conditionnellement si is_active = false, ou toujours visible)
- birth_city
- birth_country_id (select searchable, label "Pays de naissance")
- country_id (select searchable, label "Sélection nationale")

### Section "Profil rugby"
- cap_number (label "N° d'international", helperText "Numéro chronologique de sélection")
- primary_position (select enum)
- height_cm (suffix "cm")
- weight_kg (suffix "kg")
- is_active (toggle)

### Section "Photo"
- photo_path (FileUpload avec les paramètres ci-dessus)

## 5. PlayerResource — Table (colonnes)

- photo_path → ImageColumn (miniature ronde)
- cap_number → Triable
- last_name, first_name
- country → flag_emoji
- primary_position → Badge
- is_active → IconColumn (check/cross)
- death_date → Afficher "†" + date si décédé

## 6. Migration du champ photo

Si des données existent déjà avec photo_url :
- Renommer la colonne photo_url → photo_path
- Les anciennes URLs resteront mais ne fonctionneront plus (acceptable en dev)
