import { createColumnHelper } from '@tanstack/table-core';
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { ReactTable, ShadowBox } from '../../../theme';
import dayjs from '../../../utils/dayjs';
import { IGitLog } from '../../../utils/types';
import styles from './RepositoryUpdateLog.css';

const RepositoryUpdateLog: React.FC<{
  log: Array<IGitLog>;
  name: string;
  modal: boolean;
  setModal: (show: boolean) => void;
}> = ({ log, name, modal, setModal }) => {
  const columnHelper = createColumnHelper<IGitLog>();
  const columns = [
    columnHelper.accessor('date', {
      header: __('Date', 'shgi'),
      cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('prevVersion', {
      header: __('Version', 'shgi'),
      cell: (info) =>
        info.row.original.prevVersion === info.row.original.newVersion
          ? '-'
          : info.row.original.prevVersion +
            ' 🠖 ' +
            info.row.original.newVersion,
    }),
    columnHelper.accessor('ref', {
      header: __('Trigger', 'shgi'),
      cell: (info) =>
        info.getValue() === 'push-to-deploy'
          ? __('push to deploy', 'shgi')
          : info.getValue() === 'update-trigger'
          ? __('update button', 'shgi')
          : '-',
    }),
  ];

  return modal ? (
    <ShadowBox
      title={sprintf(__('update Log "%s"', 'shgi'), name)}
      close={() => setModal(false)}
      size="medium"
    >
      <div className={styles.log}>
        {log.length === 0 ? (
          <p>{__('No entries found', 'shgi')}</p>
        ) : (
          <ReactTable columns={columns} data={log} />
        )}
      </div>
    </ShadowBox>
  ) : null;
};

export default RepositoryUpdateLog;
