import {
  flexRender,
  getCoreRowModel,
  getExpandedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import React from 'react';
import cn from '../../utils/classnames';
import {
  CellBody,
  CellHeading,
  Loader,
  Row,
  TBody,
  THead,
  Table,
} from '../index';
import styles from './ReactTable.module.css';
import ExpanderCell from './cells/ExpanderCell';

const ReactTable: React.FC<{
  className?: string;
  loading?: boolean;
  columns: any;
  data: any;
  renderSubComponent?: (props: any) => React.ReactNode;
}> = ({
  className = '',
  loading = false,
  columns,
  data,
  renderSubComponent = null,
}) => {
  const table = useReactTable({
    data,
    columns: [
      ...(Boolean(renderSubComponent)
        ? [
            {
              id: 'expander',
              header: () => null,
              cell: ({ row }) => (
                <ExpanderCell
                  isExpanded={row.getIsExpanded()}
                  onClick={row.getToggleExpandedHandler()}
                />
              ),
              maxSize: 10,
            },
          ]
        : []),
      ...columns,
    ],
    getRowCanExpand: () => Boolean(renderSubComponent),
    getCoreRowModel: getCoreRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
  });

  return (
    <div className={cn(className, styles.root)}>
      {loading ? (
        <div className={cn(styles.loader)}>
          <Loader className={cn(styles.loaderIcon)} />
        </div>
      ) : (
        <Table>
          <THead>
            {table.getHeaderGroups().map((headerGroup, headerGroupI) => (
              <Row key={headerGroupI}>
                {headerGroup.headers.map((header, headerI) => (
                  <CellHeading key={`${headerGroupI}-${headerI}`}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </CellHeading>
                ))}
              </Row>
            ))}
          </THead>
          <TBody>
            {table.getRowModel().rows.map((row, rowI) => (
              <React.Fragment key={rowI}>
                <Row>
                  {row.getVisibleCells().map((cell, cellI) => (
                    <CellBody
                      key={`${rowI}-${cellI}`}
                      style={{
                        width: cell.column.getSize(),
                      }}
                    >
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </CellBody>
                  ))}
                </Row>
                {row.getIsExpanded() && (
                  <Row>
                    <CellBody colSpan={row.getVisibleCells().length}>
                      {renderSubComponent(row.original)}
                    </CellBody>
                  </Row>
                )}
              </React.Fragment>
            ))}
          </TBody>
        </Table>
      )}
    </div>
  );
};

export default ReactTable;
