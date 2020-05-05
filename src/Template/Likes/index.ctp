<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Like[]|\Cake\Collection\CollectionInterface $likes
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('New Like'), ['action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Users'), ['controller' => 'Users', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New User'), ['controller' => 'Users', 'action' => 'add']) ?></li>
        <li><?= $this->Html->link(__('List Posts'), ['controller' => 'Posts', 'action' => 'index']) ?></li>
        <li><?= $this->Html->link(__('New Post'), ['controller' => 'Posts', 'action' => 'add']) ?></li>
    </ul>
</nav>
<div class="likes index large-9 medium-8 columns content">
    <h3><?= __('Likes') ?></h3>
    <table cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('user_id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('post_id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('deleted') ?></th>
                <th scope="col"><?= $this->Paginator->sort('created') ?></th>
                <th scope="col"><?= $this->Paginator->sort('modified') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($likes as $like): ?>
            <tr>
                <td><?= $this->Number->format($like->id) ?></td>
                <td><?= $like->has('user') ? $this->Html->link($like->user->id, ['controller' => 'Users', 'action' => 'view', $like->user->id]) : '' ?></td>
                <td><?= $like->has('post') ? $this->Html->link($like->post->id, ['controller' => 'Posts', 'action' => 'view', $like->post->id]) : '' ?></td>
                <td><?= $this->Number->format($like->deleted) ?></td>
                <td><?= h($like->created) ?></td>
                <td><?= h($like->modified) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $like->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $like->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $like->id], ['confirm' => __('Are you sure you want to delete # {0}?', $like->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')]) ?></p>
    </div>
</div>
