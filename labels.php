<?php

use rdx\bookr\Book;
use rdx\bookr\Category;
use rdx\bookr\Label;
use rdx\bookr\Model;

require 'inc.bootstrap.php';

$labels = Label::allSorted();
Label::eager('num_books', $labels);
$categories = Category::allSorted();

if ( isset($_POST['categories']) ) {
	foreach ( $_POST['categories'] as $id => $data ) {
		$data['show_in_list'] = (int) !empty($data['show_in_list']);
		$data['required'] = (int) !empty($data['required']);
		$data['multiple'] = (int) !empty($data['multiple']);
		if ( $id && isset($categories[$id]) ) {
			$category = $categories[$id];
			$category->update($data);
		}
		elseif ( trim($data['name']) ) {
			Category::insert($data);
		}
	}

	do_redirect('labels');
	exit;
}

if ( isset($_POST['labels']) ) {
	foreach ( $_POST['labels'] as $id => $data ) {
		$data += [
			'default_on' => (int) !empty($data['default_on']),
		];
		if ( $id && isset($labels[$id]) ) {
			$label = $labels[$id];
			$label->update($data);
		}
		elseif ( trim($data['name']) ) {
			Label::insert($data);
		}
	}

	do_redirect('labels');
	exit;
}

include 'tpl.header.php';

$categoryOptions = Model::options($categories);

$labels[] = new Label(['id' => 0]);
$categories[] = new Category(['id' => 0]);

?>
<h1>Labels</h1>

<form action method="post">
	<table border="1" cellpadding="6">
		<thead>
			<tr>
				<th>Name</th>
				<th>Category</th>
				<th>Default ON</th>
				<th>Order</th>
				<th align="right">Usage</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($labels as $label): ?>
				<tr>
					<td><input name="labels[<?= $label->id ?>][name]" value="<?= html($label->name) ?>" <? if ($label->id): ?>required<? endif ?> /></td>
					<td><select name="labels[<?= $label->id ?>][category_id]" <? if ($label->id): ?>required<? endif ?>><?= html_options($categoryOptions, $label->category_id, '--') ?></select></td>
					<td><input type="checkbox" name="labels[<?= $label->id ?>][default_on]" <? if ($label->default_on): ?>checked<? endif ?> /></td>
					<td><input type="number" name="labels[<?= $label->id ?>][weight]" value="<?= html($label->weight) ?>" <? if ($label->id): ?>required<? endif ?> /></td>
					<td align="right"><? if ($label->id): ?><?= $label->num_books ?><? endif ?></td>
				</tr>
			<? endforeach ?>
		</tbody>
	</table>

	<p><button class="submit">Save</button></p>
</form>

<h2>Categories</h2>

<form action method="post">
	<table border="1" cellpadding="6">
		<thead>
			<tr>
				<th>Name</th>
				<th>Show in list</th>
				<th>Required</th>
				<th>Multiple</th>
				<th>Order</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($categories as $category): ?>
				<tr>
					<td><input name="categories[<?= $category->id ?>][name]" value="<?= html($category->name) ?>" <? if ($category->id): ?>required<? endif ?> /></td>
					<td><input type="checkbox" name="categories[<?= $category->id ?>][show_in_list]" <? if ($category->show_in_list): ?>checked<? endif ?> /></td>
					<td><input type="checkbox" name="categories[<?= $category->id ?>][required]" <? if ($category->required): ?>checked<? endif ?> /></td>
					<td><input type="checkbox" name="categories[<?= $category->id ?>][multiple]" <? if ($category->multiple): ?>checked<? endif ?> /></td>
					<td><input type="number" name="categories[<?= $category->id ?>][weight]" value="<?= html($category->weight) ?>" <? if ($category->id): ?>required<? endif ?> /></td>
				</tr>
			<? endforeach ?>
		</tbody>
	</table>

	<p><button class="submit">Save</button></p>
</form>

<?php

include 'tpl.footer.php';
