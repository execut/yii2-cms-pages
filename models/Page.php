<?php

namespace infoweb\pages\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use creocoder\translateable\TranslateableBehavior;
use infoweb\pages\behaviors\HomepageBehavior;
use infoweb\alias\behaviors\AliasBehavior;
use infoweb\seo\behaviors\SeoBehavior;
use infoweb\alias\traits\AliasRelationTrait;

/**
 * This is the model class for table "pages".
 *
 * @property integer $id
 * @property string $template
 * @property integer $active
 * @property string $created_at
 * @property string $time_updated
 *
 * @property PagesLang[] $pagesLangs
 */
class Page extends ActiveRecord
{
    use AliasRelationTrait;

    const TYPE_SYSTEM = 'system';
    const TYPE_USER_DEFINED = 'user-defined';

    const EVENT_BEFORE_ACTIVE = 'before-active';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pages';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'translateable' => [
                'class' => TranslateableBehavior::className(),
                'translationAttributes' => [
                    'name',
                    'title',
                    'content'
                ]
            ],
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function() { return time(); },
            ],
            'homepage'  => [
                'class' => HomepageBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'homepage',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'homepage',
                ],
            ],
            'seo' => [
                'class' => SeoBehavior::className(),
                'titleAttribute' => 'title',
            ],
            'image' => [
                'class' => 'infoweb\cms\behaviors\ImageBehave',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['template_id'], 'required'],
            [['position'], 'default', 'value' => 1],
            [['active', 'public', 'template_id', 'created_at', 'updated_at', 'slider_id', 'menu_id'], 'integer'],
            // Types
            [['type'], 'string'],
            ['type', 'in', 'range' => ['system', 'user-defined']],
            // Default type to 'user-defined'
            ['type', 'default', 'value' => 'user-defined'],
            ['public', 'default', 'value' => Yii::$app->getModule('pages')->defaultPublicVisibility],
            [['slider_id', 'menu_id', 'form_id', 'homepage'], 'default', 'value' => 0]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type' => Yii::t('app', 'Type'),
            'template_id' => Yii::t('app', 'Template'),
            'homepage' => Yii::t('infoweb/pages', 'Homepage'),
            'active' => Yii::t('app', 'Active'),
            'public' => Yii::t('infoweb/pages', 'Public'),
            'slider_id' => Yii::t('infoweb/sliders', 'Slider'),
            'menu_id' => Yii::t('infoweb/menu', 'Menu'),
            'form_id' => Yii::t('infoweb/menu', 'Form')
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTranslations()
    {
        return $this->hasMany(Lang::className(), ['page_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTemplate()
    {
        return $this->hasOne(PageTemplate::className(), ['id' => 'template_id'])->where(['active' => 1]);
    }

    /**
     * Returns the layout model for the page
     *
     * @return  frontend\models\layout\Layout
     */
    public function getLayout()
    {
        return Yii::createObject(['class' => "frontend\models\layout\\{$this->template->layout_model}"]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSlider()
    {
        if (Yii::$app->getModule('pages')->enableSliders) {
            return $this->hasOne(\infoweb\sliders\models\Slider::className(), ['id' => 'slider_id']);
        } else {
            return null;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenu()
    {
        if (Yii::$app->getModule('pages')->enableMenu) {
            return $this->hasOne(\infoweb\menu\models\Menu::className(), ['id' => 'menu_id']);
        } else {
            return null;
        }
    }

    /**
     * @return null || \infoweb\form\models\form\Form
     */
    public function getForm()
    {
        if (Yii::$app->getModule('pages')->enableForm && (int) $this->form_id != 0) {
            return \infoweb\form\models\form\Form::findById( (int) $this->form_id );
        } else {
            return null;
        }
    }

    /**
     * Checks if a page is used in a menu
     *
     * @return  boolean
     */
    public function isUsedInMenu()
    {
        return (new \yii\db\Query)
                    ->select('id')
                    ->from(\infoweb\menu\models\MenuItem::tableName())
                    ->where([
                        'entity'    => \infoweb\menu\models\MenuItem::ENTITY_PAGE,
                        'entity_id' => $this->id
                    ])
                    ->exists();
    }

    /**
     * Returns the html anchors that are used in the content of a page
     *
     * @return  array
     */
    public function getHtmlAnchors()
    {
        // Parse the anchors out of the page content
        // They should be found in the format '<a id="" name=""></a>'
        $anchors = [];
        $content = $this->content;
        preg_match_all('/<a id="([^\"]+)" name="([^\"]+)">[^<]*<\/a>/', $content, $matches);

        // The 'id' tag is used as key and the 'name' tag as value of the anchors array
        if (isset($matches[1]) && isset($matches[2])) {
            $anchors = array_combine($matches[1], $matches[2]);
        }

        return $anchors;
    }

    /**
     * Returns the url of the page
     *
     * @param string $includeLanguage Option to determine if the language should
     *                                be included in the url.
     * @param mixed $language The language that has to be used for the url
     * @return string $url The complete url
     */
    public function getUrl($includeLanguage = true, $language = null, $excludeWebPath = false)
    {
        $url = ($excludeWebPath) ? '' : Yii::getAlias('@baseUrl') . '/';
        $language = ($language) ?: Yii::$app->language;

        if ($includeLanguage) {
            $url .= $language . '/';
        }

        if ($this->alias && !$this->homepage) {
            $url .= $this->alias->url;
        }

        return $url;
    }

    /**
     * Returns all items formatted for usage in a Html::dropDownList widget:
     *      [
     *          'id' => 'name',
     *          'id' => 'name,
     *          ...
     *      ]
     *
     * @return  array
     */
    public static function getAllForDropDownList($language = null)
    {
        $language = ($language) ?: Yii::$app->language;

        $items = (new Query())
                    ->select('page.id, page_lang.name')
                    ->from(['page' => 'pages'])
                    ->innerJoin(['page_lang' => 'pages_lang'], "page.id = page_lang.page_id AND page_lang.language = :language")
                    ->addParams([':language' => $language])
                    ->orderBy(['page_lang.name' => SORT_ASC])
                    ->all();        

        return ArrayHelper::map($items, 'id', 'name');
    }
}
