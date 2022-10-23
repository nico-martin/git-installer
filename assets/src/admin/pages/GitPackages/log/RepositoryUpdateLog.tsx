import { createColumnHelper } from '@tanstack/table-core';
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
  Loader,
  Notice,
  NOTICE_TYPES,
  ReactTable,
  ShadowBox,
} from '../../../theme';
import { apiGet } from '../../../utils/apiFetch';
import { VARS } from '../../../utils/constants';
import { IGitLog } from '../../../utils/types';
import styles from './RepositoryUpdateLog.css';

const RepositoryUpdateLog: React.FC<{
  //log: Array<IGitLog>;
  repoKey: string;
  name: string;
  modal: boolean;
  setModal: (show: boolean) => void;
}> = ({ repoKey, name, modal, setModal }) => {
  const [logs, setLogs] = React.useState<Array<IGitLog>>([]);
  const [logsLoading, setLogsLoading] = React.useState<boolean>(false);
  const [logsError, setLogsError] = React.useState<string>('');
  const columnHelper = createColumnHelper<IGitLog>();

  React.useEffect(() => {
    setLogs([]);
    setLogsError('');
    setLogsLoading(false);
    if (modal) {
      setLogsLoading(true);
      apiGet<Array<IGitLog>>(
        `${VARS.restPluginNamespace}/packages-update-log/${repoKey}/`
      )
        .then((logs) => setLogs(logs))
        .catch((e) => setLogsError(e))
        .finally(() => setLogsLoading(false));
    }
  }, [modal]);

  const columns = [
    columnHelper.accessor('date', {
      header: __('Date', 'shgi'),
      cell: (info) => info.getValue(),
      sortingFn: (a, b) => a.original.time - b.original.time,
    }),
    columnHelper.accessor('prevVersion', {
      header: __('Version', 'shgi'),
      cell: (info) =>
        info.row.original.prevVersion === info.row.original.newVersion
          ? '-'
          : info.row.original.prevVersion +
            ' 🠖 ' +
            info.row.original.newVersion,
      enableSorting: false,
    }),
    columnHelper.accessor('refName', {
      header: __('Trigger', 'shgi'),
      cell: (info) => info.getValue(),
    }),
  ];

  return modal ? (
    <ShadowBox
      title={sprintf(__('update Log "%s"', 'shgi'), name)}
      close={() => setModal(false)}
      size="medium"
    >
      <div className={styles.log}>
        {logsLoading ? (
          <Loader block />
        ) : logsError ? (
          <Notice type={NOTICE_TYPES.ERROR}>{logsError}</Notice>
        ) : logs.length === 0 ? (
          <p>{__('No entries found', 'shgi')}</p>
        ) : (
          <ReactTable
            columns={columns}
            data={logs}
            initialSort={[{ id: 'date', desc: true }]}
            enableSort
          />
        )}
      </div>
    </ShadowBox>
  ) : null;
};

export default RepositoryUpdateLog;
