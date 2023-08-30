import React, { MutableRefObject } from 'react';
import { usePopper } from 'react-popper';
import cn from '../../utils/classnames';
import styles from './Tooltip.css';

let i = 0;

const Tooltip: React.FC<{
  children: any;
  tooltipRef: MutableRefObject<HTMLElement>;
  triggerRef?: MutableRefObject<HTMLElement>;
  maxWidth?: number;
}> = ({
  children,
  tooltipRef,
  triggerRef: customTriggerRef = null,
  maxWidth = null,
}) => {
  const [show, setShow] = React.useState<boolean>(false);
  const popperRef = React.useRef<HTMLDivElement>(null);

  const {
    styles: popperStyles,
    attributes,
    //update,
  } = usePopper(tooltipRef?.current, popperRef?.current, {
    placement: 'bottom',
  });

  const id: string = React.useMemo(() => {
    i++;
    tooltipRef?.current &&
      tooltipRef.current.setAttribute('aria-describedby', `tooltip${i}`);
    return `tooltip${i}`;
  }, [tooltipRef?.current]);

  const addListeners = (element: HTMLElement) => {
    if (element) {
      element.addEventListener('mouseover', () => setShow(true));
      element.addEventListener('mouseleave', () => setShow(false));
    }
  };

  const removeListeners = (element: HTMLElement) => {
    if (element) {
      element.removeEventListener('mouseover', () => setShow(true));
      element.removeEventListener('mouseleave', () => setShow(false));
    }
  };

  React.useEffect(() => {
    addListeners(
      customTriggerRef ? customTriggerRef?.current : tooltipRef?.current
    );
    return () =>
      removeListeners(
        customTriggerRef ? customTriggerRef?.current : tooltipRef?.current
      );
  }, [tooltipRef?.current, customTriggerRef?.current]);

  return (
    <span
      ref={popperRef}
      className={cn(styles.tooltip, { [styles.tooltipShow]: show })}
      role="tooltip"
      id={id}
      aria-hidden={!show}
      style={{ ...popperStyles.popper, ...(maxWidth ? { maxWidth } : {}) }}
      {...attributes.popper}
    >
      <span className={styles.tooltipInner}>
        {children}
        <span className={styles.arrow} style={popperStyles.arrow} />
      </span>
    </span>
  );
};

export default Tooltip;
