<div class="filters" id="custom-filters">
    <b><?= lang('filters') ?>:</b>

    <ul>
        <li>
            <input type="hidden" name="search_status" value="<?= $search_status ?>">
            <a href="" class="has-sub" data-filter-label="status">
                <?= lang('status') ?>
                <span class="faded">
                    <?= $search_status ? '(' . lang($search_status) . ')' : '' ?>
                </span>
            </a>
            <div class="sub-menu">
                <ul data-target="search_status">
                    <?php foreach ($form_statuses as $status => $status_label): ?>
                        <li>
                            <a data-value="<?= $status ?>">
                                <?= $status_label ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </li>
        <li>
            <input type="hidden" name="search_date_range" value="<?= $search_date_range ?>">
            <a href="" class="has-sub" data-filter-label="date">
                <?= lang('entry_date') ?>
                <span class="faded">
                    <?= $search_date_range ? '(' . lang($search_date_range) . ')' : '' ?>
                </span>
            </a>
            <div class="sub-menu">
                <ul data-target="search_date_range">
                    <li><a data-value="today"><?= lang('today') ?></a></li>
                    <li><a data-value="this_week"><?= lang('this_week') ?></a></li>
                    <li><a data-value="this_month"><?= lang('this_month') ?></a></li>
                    <li><a data-value="last_month"><?= lang('last_month') ?></a></li>
                    <li><a data-value="this_year"><?= lang('this_year') ?></a></li>
                    <li><a data-value="date_range" data-prevent-trigger="1"><?= lang('choose_date_range') ?></a></li>
                </ul>
            </div>
        </li>

        <li id="date-range-inputs">
            <input type="text"
                   name="search_date_range_start"
                   class="datepicker"
                   rel="date-picker"
                   data-timestamp="<?= $search_date_range_start ? strtotime($search_date_range_start) : time() ?>"
                   value="<?= $search_date_range_start ?>"
                   placeholder="<?= lang('start_date') ?>"
                   style="width: 70px;"
            />
            <input type="text"
                   name="search_date_range_end"
                   class="datepicker"
                   rel="date-picker"
                   data-timestamp="<?= $search_date_range_start ? strtotime($search_date_range_start) : time() ?>"
                   value="<?= $search_date_range_end ?>"
                   placeholder="<?= lang('end_date') ?>"
                   style="width: 70px;"
            />
        </li>


        <li>
            <input type="text"
                   name="search_keywords"
                   placeholder="<?= lang('keywords') ?>"
                   style="width: 90px;"
                   value="<?= $search_keywords ?>"
            />
        </li>
        <li>
            <input type="hidden" name="search_on_field" value="<?= $search_on_field ?>">
            <a href="" class="has-sub" data-filter-label="date">
                <?= lang('field') ?>
                <span class="faded">
                    (<?= $search_on_field ? $column_labels[$search_on_field] : lang('all_fields') ?>)
                </span>
            </a>
            <div class="sub-menu">
                <ul data-target="search_on_field">
                    <li>
                        <a data-value=""><?= lang('all_fields') ?></a>
                    </li>
                    <?php foreach ($visible_columns as $column_name): ?>
                        <li>
                            <a data-value="<?= $column_name ?>">
                                <?= $column_labels[$column_name] ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </li>

        <li class="filter-clear">
            <a href="<?= $base_url ?>"><?= lang('clear_filters') ?></a>
        </li>
    </ul>
</div>
