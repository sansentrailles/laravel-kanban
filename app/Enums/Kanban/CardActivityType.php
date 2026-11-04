<?php

declare(strict_types=1);

namespace App\Enums\Kanban;

enum CardActivityType: string
{
    // Карточка
    case CardCreated = 'card.created';
    case CardUpdated = 'card.updated';
    case CardMoved = 'card.moved';
    case CardLabelsChanged = 'card.labels_changed';
    case CardAssigneesChanged = 'card.assignees_changed';
    case CardDeleted = 'card.deleted';
    case CardRestored = 'card.restored';
    case CardCompleted = 'card.completed';
    case CardReopened = 'card.reopened';

    // Комментарии
    case CommentCreated = 'comment.created';
    case CommentUpdated = 'comment.updated';
    case CommentDeleted = 'comment.deleted';

    // Чек-листы
    case ChecklistCreated = 'checklist.created';
    case ChecklistDeleted = 'checklist.deleted';
    case ChecklistItemCreated = 'checklist_item.created';
    case ChecklistItemToggled = 'checklist_item.toggled';
    case ChecklistItemDeleted = 'checklist_item.deleted';

    // Вложения
    case AttachmentUploaded = 'attachment.uploaded';
    case AttachmentDeleted = 'attachment.deleted';

    public function subjectType(): string
    {
        return match ($this) {
            self::CardCreated,
            self::CardUpdated,
            self::CardMoved,
            self::CardLabelsChanged,
            self::CardAssigneesChanged,
            self::CardDeleted,
            self::CardRestored,
            self::CardCompleted,
            self::CardReopened => 'card',

            self::CommentCreated,
            self::CommentUpdated,
            self::CommentDeleted => 'comment',

            self::ChecklistCreated,
            self::ChecklistDeleted => 'checklist',

            self::ChecklistItemCreated,
            self::ChecklistItemToggled,
            self::ChecklistItemDeleted => 'checklist_item',

            self::AttachmentUploaded,
            self::AttachmentDeleted => 'attachment',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CardCreated => 'Создание карточки',
            self::CardUpdated => 'Изменение карточки',
            self::CardMoved => 'Перемещение карточки',
            self::CardLabelsChanged => 'Изменение меток',
            self::CardAssigneesChanged => 'Изменение исполнителей',
            self::CardDeleted => 'Удаление карточки',
            self::CardRestored => 'Восстановление карточки',
            self::CardCompleted => 'Завершение карточки',
            self::CardReopened => 'Возврат карточки в работу',
            self::CommentCreated => 'Добавление комментария',
            self::CommentUpdated => 'Редактирование комментария',
            self::CommentDeleted => 'Удаление комментария',
            self::ChecklistCreated => 'Создание чек-листа',
            self::ChecklistDeleted => 'Удаление чек-листа',
            self::ChecklistItemCreated => 'Добавление пункта чек-листа',
            self::ChecklistItemToggled => 'Переключение пункта чек-листа',
            self::ChecklistItemDeleted => 'Удаление пункта чек-листа',
            self::AttachmentUploaded => 'Загрузка вложения',
            self::AttachmentDeleted => 'Удаление вложения',
        };
    }
}
