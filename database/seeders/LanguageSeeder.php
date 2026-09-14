<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use App\Services\Translator;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    private const LANGUAGES = [
        'en' => ['name' => 'English', 'native_name' => 'English', 'position' => 1],
        'ru' => ['name' => 'Russian', 'native_name' => 'Русский', 'position' => 2],
        'es' => ['name' => 'Spanish', 'native_name' => 'Español', 'position' => 3],
        'fr' => ['name' => 'French', 'native_name' => 'Français', 'position' => 4],
        'pt' => ['name' => 'Portuguese (Brazil)', 'native_name' => 'Português (Brasil)', 'position' => 5],
    ];

    /*
     * TERMINOLOGY
     * English | Russian | Spanish | French | Portuguese (Brazil) | Why
     * Trainee | Стажёр | Aprendiz | Apprenant | Aprendiz | Person taking operational training; not a school student.
     * Trainer | Наставник | Mentor | Formateur | Instrutor | Person guiding learning; avoids gym/training connotations in Russian.
     * Dashboard | Панель | Panel | Tableau | Painel | Compact admin surface label.
     * Settings | Настройки | Ajustes | Paramètres | Configurações | Spanish uses Ajustes for product settings.
     */
    public function run(): void
    {
        $languages = [];

        foreach (self::LANGUAGES as $code => $data) {
            $languages[$code] = Language::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'native_name' => $data['native_name'],
                    'is_active' => true,
                    'is_default' => false,
                    'direction' => 'ltr',
                    'position' => $data['position'],
                ],
            );
        }

        if (! Language::where('is_default', true)->exists()) {
            $languages['en']->forceFill(['is_default' => true, 'is_active' => true])->save();
        }

        foreach ($this->strings() as $key => $values) {
            foreach ($values as $code => $value) {
                Translation::firstOrCreate(
                    ['key' => $key, 'language_id' => $languages[$code]->id],
                    [
                        'value' => $value,
                        'module' => str($key)->before('.')->value(),
                    ],
                );
            }
        }

        app(Translator::class)->clearLanguageCaches();
        foreach (array_keys(self::LANGUAGES) as $code) {
            app(Translator::class)->clearBundleCache($code);
        }
    }

    private function strings(): array
    {
        return array_merge(
            $this->coreVocabulary(),
            $this->navigation(),
            $this->auth(),
            $this->fields(),
            $this->locale(),
            $this->guides(),
            $this->admin(),
            $this->mail(),
        );
    }

    private function coreVocabulary(): array
    {
        return [
            'core.trainee' => ['en' => 'Trainee', 'ru' => 'Стажёр', 'es' => 'Aprendiz', 'fr' => 'Apprenant', 'pt' => 'Aprendiz'],
            'core.trainer' => ['en' => 'Trainer', 'ru' => 'Наставник', 'es' => 'Mentor', 'fr' => 'Formateur', 'pt' => 'Instrutor'],
            'core.dashboard' => ['en' => 'Dashboard', 'ru' => 'Панель', 'es' => 'Panel', 'fr' => 'Tableau', 'pt' => 'Painel'],
            'core.settings' => ['en' => 'Settings', 'ru' => 'Настройки', 'es' => 'Ajustes', 'fr' => 'Paramètres', 'pt' => 'Configurações'],
        ];
    }

    private function navigation(): array
    {
        return [
            'nav.skip' => ['en' => 'Skip to content', 'ru' => 'Перейти к содержимому', 'es' => 'Saltar al contenido', 'fr' => 'Aller au contenu', 'pt' => 'Ir para o conteúdo'],
            'nav.help' => ['en' => 'Help', 'ru' => 'Помощь', 'es' => 'Ayuda', 'fr' => 'Aide', 'pt' => 'Ajuda'],
            'nav.certificates' => ['en' => 'Certificates', 'ru' => 'Сертификаты', 'es' => 'Certificados', 'fr' => 'Certificats', 'pt' => 'Certificados'],
            'nav.logout' => ['en' => 'Log out', 'ru' => 'Выйти', 'es' => 'Cerrar sesión', 'fr' => 'Se déconnecter', 'pt' => 'Sair'],
            'footer.internal_training' => ['en' => 'internal training', 'ru' => 'внутреннее обучение', 'es' => 'formación interna', 'fr' => 'formation interne', 'pt' => 'treinamento interno'],
        ];
    }

    private function auth(): array
    {
        return [
            'auth.login' => ['en' => 'Log in', 'ru' => 'Войти', 'es' => 'Iniciar sesión', 'fr' => 'Connexion', 'pt' => 'Entrar'],
            'auth.register' => ['en' => 'Register', 'ru' => 'Регистрация', 'es' => 'Registrarse', 'fr' => 'S’inscrire', 'pt' => 'Cadastrar'],
            'auth.login_title' => ['en' => 'Log in - Pilot Academy', 'ru' => 'Войти - Pilot Academy', 'es' => 'Iniciar sesión - Pilot Academy', 'fr' => 'Connexion - Pilot Academy', 'pt' => 'Entrar - Pilot Academy'],
            'auth.login_meta' => [
                'en' => 'Log in to Pilot Academy to reach your courses on the Pilot vehicle monitoring platform and pick up your saved progress.',
                'ru' => 'Войдите в Pilot Academy, чтобы открыть курсы по платформе мониторинга транспорта Pilot и продолжить с сохранённого места.',
                'es' => 'Inicia sesión en Pilot Academy para acceder a tus cursos de la plataforma de monitoreo vehicular Pilot y continuar tu progreso.',
                'fr' => 'Connectez-vous à Pilot Academy pour accéder à vos cours sur la plateforme de suivi des véhicules Pilot et reprendre votre progression.',
                'pt' => 'Entre na Pilot Academy para acessar seus cursos na plataforma de monitoramento veicular Pilot e continuar seu progresso salvo.',
            ],
            'auth.login_intro' => ['en' => 'Access your courses and saved progress.', 'ru' => 'Откройте свои курсы и сохранённый прогресс.', 'es' => 'Accede a tus cursos y al progreso guardado.', 'fr' => 'Accédez à vos cours et à votre progression enregistrée.', 'pt' => 'Acesse seus cursos e seu progresso salvo.'],
            'auth.remember_me' => ['en' => 'Remember me', 'ru' => 'Запомнить меня', 'es' => 'Recordarme', 'fr' => 'Se souvenir de moi', 'pt' => 'Lembrar de mim'],
            'auth.no_account' => ['en' => 'No account?', 'ru' => 'Нет аккаунта?', 'es' => '¿No tienes cuenta?', 'fr' => 'Pas de compte ?', 'pt' => 'Não tem conta?'],
        ];
    }

    private function fields(): array
    {
        return [
            'field.email' => ['en' => 'Email', 'ru' => 'Эл. почта', 'es' => 'Correo electrónico', 'fr' => 'E-mail', 'pt' => 'E-mail'],
            'field.password' => ['en' => 'Password', 'ru' => 'Пароль', 'es' => 'Contraseña', 'fr' => 'Mot de passe', 'pt' => 'Senha'],
        ];
    }

    private function locale(): array
    {
        return [
            'locale.choose' => ['en' => 'Choose language', 'ru' => 'Выберите язык', 'es' => 'Elegir idioma', 'fr' => 'Choisir la langue', 'pt' => 'Escolher idioma'],
            'locale.invalid' => ['en' => 'Choose an active language.', 'ru' => 'Выберите активный язык.', 'es' => 'Elige un idioma activo.', 'fr' => 'Choisissez une langue active.', 'pt' => 'Escolha um idioma ativo.'],
        ];
    }

    private function guides(): array
    {
        return [
            'help.title' => ['en' => 'Help - Pilot Academy', 'ru' => 'Помощь - Pilot Academy', 'es' => 'Ayuda - Pilot Academy', 'fr' => 'Aide - Pilot Academy', 'pt' => 'Ajuda - Pilot Academy'],
            'help.meta' => [
                'en' => 'How Pilot Academy works: lessons, knowledge checks, the final quiz and certificates.',
                'ru' => 'Как работает Pilot Academy: уроки, проверки знаний, финальный тест и сертификаты.',
                'es' => 'Cómo funciona Pilot Academy: lecciones, comprobaciones de conocimiento, examen final y certificados.',
                'fr' => 'Fonctionnement de Pilot Academy : leçons, contrôles de connaissances, quiz final et certificats.',
                'pt' => 'Como a Pilot Academy funciona: aulas, verificações de conhecimento, teste final e certificados.',
            ],
            'help.heading' => ['en' => 'Help', 'ru' => 'Помощь', 'es' => 'Ayuda', 'fr' => 'Aide', 'pt' => 'Ajuda'],
            'help.unavailable' => [
                'en' => 'The help guide is not available right now.',
                'ru' => 'Справка сейчас недоступна.',
                'es' => 'La guía de ayuda no está disponible en este momento.',
                'fr' => 'Le guide d’aide n’est pas disponible pour le moment.',
                'pt' => 'O guia de ajuda não está disponível no momento.',
            ],
            'help.contents_aria' => ['en' => 'Help contents', 'ru' => 'Содержание справки', 'es' => 'Contenido de la ayuda', 'fr' => 'Sommaire de l’aide', 'pt' => 'Conteúdo da ajuda'],
            'help.still_stuck' => [
                'en' => 'Still stuck? Contact your academy administrator.',
                'ru' => 'Все еще нужна помощь? Свяжитесь с администратором академии.',
                'es' => '¿Sigues atascado? Contacta con el administrador de tu academia.',
                'fr' => 'Encore bloqué ? Contactez l’administrateur de votre académie.',
                'pt' => 'Ainda com dificuldade? Fale com o administrador da academia.',
            ],
            'help.back_to_courses' => ['en' => 'Back to courses', 'ru' => 'Назад к курсам', 'es' => 'Volver a los cursos', 'fr' => 'Retour aux cours', 'pt' => 'Voltar aos cursos'],
            'guide.admin.unavailable' => [
                'en' => 'The guide file docs/admin-guide.md was not found.',
                'ru' => 'Файл руководства docs/admin-guide.md не найден.',
                'es' => 'No se encontró el archivo de guía docs/admin-guide.md.',
                'fr' => 'Le fichier de guide docs/admin-guide.md est introuvable.',
                'pt' => 'O arquivo do guia docs/admin-guide.md não foi encontrado.',
            ],
            'guide.search_label' => ['en' => 'Search the guide', 'ru' => 'Искать в руководстве', 'es' => 'Buscar en la guía', 'fr' => 'Rechercher dans le guide', 'pt' => 'Pesquisar no guia'],
            'guide.search_placeholder' => ['en' => 'Search the guide...', 'ru' => 'Искать в руководстве...', 'es' => 'Buscar en la guía...', 'fr' => 'Rechercher dans le guide...', 'pt' => 'Pesquisar no guia...'],
            'guide.contents' => ['en' => 'Contents', 'ru' => 'Содержание', 'es' => 'Contenido', 'fr' => 'Sommaire', 'pt' => 'Conteúdo'],
            'guide.contents_aria' => ['en' => 'Guide contents', 'ru' => 'Содержание руководства', 'es' => 'Contenido de la guía', 'fr' => 'Sommaire du guide', 'pt' => 'Conteúdo do guia'],
            'guide.no_results_prefix' => [
                'en' => 'Nothing in the guide mentions',
                'ru' => 'В руководстве ничего не найдено по запросу',
                'es' => 'Nada en la guía menciona',
                'fr' => 'Rien dans le guide ne mentionne',
                'pt' => 'Nada no guia menciona',
            ],
        ];
    }

    private function admin(): array
    {
        return [
            'admin.languages' => ['en' => 'Languages', 'ru' => 'Языки', 'es' => 'Idiomas', 'fr' => 'Langues', 'pt' => 'Idiomas'],
            'admin.translations' => ['en' => 'Translations', 'ru' => 'Переводы', 'es' => 'Traducciones', 'fr' => 'Traductions', 'pt' => 'Traduções'],
            'admin.localization' => ['en' => 'Localization', 'ru' => 'Локализация', 'es' => 'Localización', 'fr' => 'Localisation', 'pt' => 'Localização'],
        ];
    }

    private function mail(): array
    {
        return [
            'mail.course_reminder.subject' => [
                'en' => 'Pick up where you left off - Pilot Academy',
                'ru' => 'Продолжите с того места, где остановились - Pilot Academy',
                'es' => 'Continúa donde lo dejaste - Pilot Academy',
                'fr' => 'Reprenez là où vous vous êtes arrêté - Pilot Academy',
                'pt' => 'Continue de onde parou - Pilot Academy',
            ],
        ];
    }
}
